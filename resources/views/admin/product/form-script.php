<script>
document.addEventListener('DOMContentLoaded', function() {
    const productTypeSelect = document.querySelector('select[name="product_type"]');
    const variantSection = document.getElementById('variant-section');
    const btnAddVariant = document.getElementById('btnAddVariant');
    const variantsList = document.getElementById('variantsList');
    
    // Inject PHP data
    const initialVariants = <?= json_encode($item['variants'] ?? []) ?>;
    const allAttributes = <?= json_encode($attributes ?? []) ?>;

    let variantCounter = 0;

    function toggleVariantSection() {
        if (productTypeSelect.value === 'variable') {
            variantSection.style.display = 'block';
        } else {
            variantSection.style.display = 'none';
        }
    }

    function createAttributeSelects(variantIndex, selectedAttributes) {
        let html = '';
        allAttributes.forEach(attr => {
            let selectedVal = selectedAttributes ? (selectedAttributes[attr.id_code] || '') : '';
            html += `<div class="mb-1">
                <small class="text-muted">${attr.name}</small>
                <select name="variants[${variantIndex}][attributes][${attr.id_code}]" class="form-select form-select-sm">
                    <option value="">- Chọn -</option>`;
            
            if (attr.values) {
                attr.values.forEach(val => {
                    const isSelected = selectedVal == val.id_code ? 'selected' : '';
                    html += `<option value="${val.id_code}" ${isSelected}>${val.name}</option>`;
                });
            }
            html += `</select></div>`;
        });
        return html;
    }

    function addVariantRow(variant) {
        const index = variantCounter++;
        const id = variant ? (variant.id || 0) : 0;
        const sku = variant ? (variant.sku || '') : '';
        const price = variant ? (variant.price || 0) : 0;
        const promoPrice = variant ? (variant.promotional_price || 0) : 0;
        const stock = variant ? (variant.stock_quantity || 0) : 0;
        const img = variant ? (variant.image || '') : '';
        const attributes = variant ? (variant.attributes || {}) : null;

        const attrHtml = createAttributeSelects(index, attributes);

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">
                <input type="hidden" name="variants[${index}][id]" value="${id}">
                <div class="mb-2">
                    <img src="${img ? '/' + img : 'https://placehold.co/100x100?text=No+Img'}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;" id="img-preview-${index}">
                </div>
                <input type="text" name="variants[${index}][image]" class="form-control form-control-sm text-center" value="${img}" placeholder="Đường dẫn ảnh" onchange="document.getElementById('img-preview-${index}').src = '/' + this.value;">
            </td>
            <td><input type="text" name="variants[${index}][sku]" class="form-control form-control-sm" value="${sku}" placeholder="SKU"></td>
            <td><input type="number" name="variants[${index}][price]" class="form-control form-control-sm" value="${price}"></td>
            <td><input type="number" name="variants[${index}][promotional_price]" class="form-control form-control-sm" value="${promoPrice}"></td>
            <td><input type="number" name="variants[${index}][stock_quantity]" class="form-control form-control-sm" value="${stock}"></td>
            <td>${attrHtml}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant"><i class="fa-solid fa-trash"></i></button>
            </td>
        `;
        variantsList.appendChild(tr);
    }

    productTypeSelect.addEventListener('change', toggleVariantSection);
    
    btnAddVariant.addEventListener('click', function() {
        addVariantRow(null);
    });

    variantsList.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-variant');
        if (btn) {
            if (confirm('Xóa biến thể này?')) {
                btn.closest('tr').remove();
            }
        }
    });

    // Init
    toggleVariantSection();
    if (initialVariants && initialVariants.length > 0) {
        initialVariants.forEach(v => addVariantRow(v));
    } else {
        // addVariantRow(null); // Optional: add empty row if needed
    }
});
</script>
