// products_page.js
// Wait for includes/templates then initialize filter UI and render all products into #all-products

(function () {
    function fetchProducts() {
        return fetch('/php/api/products.php')
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
            .catch(err => { console.error('Error fetching products.json', err); return []; });
    }

    function uniqueValues(arr, key) {
        return Array.from(new Set(arr.map(i => i[key]).filter(Boolean))).sort();
    }

    function renderList(container, list, template) {
        container.innerHTML = '';
        list.forEach(product => {
            const node = template.content.cloneNode(true);
            const img = node.querySelector('.product-img');
            const title = node.querySelector('.product-title');
            const price = node.querySelector('.product-price');
            const link = node.querySelector('.product-link');

            if (img) { img.src = product.image || '/assets/img/products/placeholder.png'; img.alt = product.title; }
            if (title) title.textContent = product.title;
            if (price) price.textContent = `$${Number(product.price).toFixed(2)}`;
            if (link) link.href = product.url || `#/product/${product.id}`;

            container.appendChild(node);
        });
    }

    function applyFilters(products) {
        const dept = document.getElementById('filter-department').value;
        const cat = document.getElementById('filter-category').value;
        const minRaw = document.getElementById('filter-price-min').value;
        const maxRaw = document.getElementById('filter-price-max').value;
        const min = (minRaw === '' || minRaw === undefined) ? null : parseFloat(minRaw);
        const max = (maxRaw === '' || maxRaw === undefined) ? null : parseFloat(maxRaw);
        const onlyBest = document.getElementById('filter-best').checked;
        const onlyAvail = document.getElementById('filter-available').checked;

        return products.filter(p => {
            if (dept && p.department !== dept) return false;
            if (cat && p.category !== cat) return false;
            if (onlyBest && !p.best_seller) return false; // using best_seller for 'oferta'
            // availability field optional in data
            if (onlyAvail && p.available === false) return false;
            if (min !== null && Number(p.price) < min) return false;
            if (max !== null && Number(p.price) > max) return false;
            return true;
        });
    }

    function populateFilters(products) {
        const deps = uniqueValues(products, 'department');
        const cats = uniqueValues(products, 'category');
        const depSel = document.getElementById('filter-department');
        const catSel = document.getElementById('filter-category');
        deps.forEach(d => {
            const opt = document.createElement('option'); opt.value = d; opt.textContent = d; depSel.appendChild(opt);
        });
        cats.forEach(c => {
            const opt = document.createElement('option'); opt.value = c; opt.textContent = c; catSel.appendChild(opt);
        });
    }

    function init(products) {
        const container = document.getElementById('all-products');
        const template = document.querySelector('template#product-card-template');
        if (!container || !template) return;

        // populate filters
        populateFilters(products);

        const applyBtn = document.getElementById('apply-filters');
        const clearBtn = document.getElementById('clear-filters');
        const rangeMin = document.getElementById('filter-price-min');
        const rangeMax = document.getElementById('filter-price-max');
        const labelMin = document.getElementById('price-min-label');
        const labelMax = document.getElementById('price-max-label');

        // initialize slider bounds from products
        const prices = products.map(p => Number(p.price)).filter(n => !isNaN(n));
        const dataMin = Math.min(...prices, 0);
        const dataMax = Math.max(...prices, 1000);
        if (rangeMin && rangeMax) {
            rangeMin.min = dataMin;
            rangeMin.max = dataMax;
            rangeMax.min = dataMin;
            rangeMax.max = dataMax;
            rangeMin.value = dataMin;
            rangeMax.value = dataMax;
        }
        if (labelMin) labelMin.textContent = Number(rangeMin.value).toFixed(0);
        if (labelMax) labelMax.textContent = Number(rangeMax.value).toFixed(0);

        function doRender() {
            const filtered = applyFilters(products);
            renderList(container, filtered, template);
        }

        // live render when user moves sliders (immediate feedback)
        if (rangeMin) {
            rangeMin.addEventListener('input', () => {
                // prevent min exceeding max
                if (Number(rangeMin.value) > Number(rangeMax.value)) {
                    rangeMin.value = rangeMax.value;
                }
                if (labelMin) labelMin.textContent = Number(rangeMin.value).toFixed(0);
                doRender();
            });
        }
        if (rangeMax) {
            rangeMax.addEventListener('input', () => {
                // prevent max going below min
                if (Number(rangeMax.value) < Number(rangeMin.value)) {
                    rangeMax.value = rangeMin.value;
                }
                if (labelMax) labelMax.textContent = Number(rangeMax.value).toFixed(0);
                doRender();
            });
        }

        applyBtn.addEventListener('click', doRender);
        clearBtn.addEventListener('click', () => {
            document.getElementById('filter-department').value = '';
            document.getElementById('filter-category').value = '';
            // reset slider to dataset bounds
            if (rangeMin && rangeMax) {
                rangeMin.value = rangeMin.min;
                rangeMax.value = rangeMax.max;
                if (labelMin) labelMin.textContent = Number(rangeMin.value).toFixed(0);
                if (labelMax) labelMax.textContent = Number(rangeMax.value).toFixed(0);
            }
            document.getElementById('filter-best').checked = false;
            document.getElementById('filter-available').checked = false;
            doRender();
        });

        // initial render
        doRender();
    }

    function afterIncludes(fn) {
        if (document.querySelector('template#product-card-template')) fn();
        else document.addEventListener('includes:loaded', fn, { once: true });
    }

    afterIncludes(async () => {
        const products = await fetchProducts();
        init(products);
    });
})();
