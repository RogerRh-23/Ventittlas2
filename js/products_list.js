// products_list.js
// Fetches /assets/data/products.json and renders product cards into containers marked with [data-products]

(async function () {
    async function fetchProducts() {
        const url = '/php/api/products.php';
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (err) {
            console.error('Error fetching products.json', err);
            return [];
        }
    }

    function renderProduct(container, product, template) {
        const node = template.content.cloneNode(true);
        const img = node.querySelector('.product-img');
        const title = node.querySelector('.product-title');
        const price = node.querySelector('.product-price');
        const link = node.querySelector('.product-link');

        if (img) img.src = product.image || '/assets/img/products/placeholder.png';
        if (img) img.alt = product.title;
        if (title) title.textContent = product.title;
        if (price) price.textContent = `$${Number(product.price).toFixed(2)}`;
        if (link) link.href = product.url || `#/product/${product.id}`;

        container.appendChild(node);
    }

    function matchesFilter(product, attrs) {
        // attrs may have data-filter="best_seller" or data-department / data-category
        if (attrs.filter) {
            // treat 'best_seller' specially (boolean)
            if (attrs.filter === 'best_seller') return !!product.best_seller;
            return product[attrs.filter];
        }
        if (attrs.department) {
            return product.department === attrs.department;
        }
        if (attrs.category) {
            return product.category === attrs.category;
        }
        return true;
    }

    function gatherAttrs(el) {
        return {
            filter: el.getAttribute('data-filter'),
            department: el.getAttribute('data-department'),
            category: el.getAttribute('data-category'),
            limit: parseInt(el.getAttribute('data-limit')) || null,
        };
    }

    // Wait for includes to be loaded (so component templates are available)
    function afterIncludes(fn) {
        if (document.querySelector('template#product-card-template')) {
            fn();
        } else {
            document.addEventListener('includes:loaded', fn, { once: true });
        }
    }

    afterIncludes(async () => {
        const products = await fetchProducts();
        const template = document.querySelector('template#product-card-template');
        if (!template) {
            console.warn('product-card-template not found in DOM. Did you include components/products.html?');
            return;
        }

        const containers = document.querySelectorAll('[data-products]');
        containers.forEach(container => {
            const attrs = gatherAttrs(container);
            let list = products.filter(p => matchesFilter(p, attrs));
            if (attrs.limit) list = list.slice(0, attrs.limit);
            // clear container
            container.innerHTML = '';
            list.forEach(p => renderProduct(container, p, template));
        });
    });
})();
