<script>
document.addEventListener('DOMContentLoaded', function() {
    const productTypeSelect = document.querySelector('select[name="product_type"]');
    
    // Tab List Items (for showing/hiding)
    const liAttributes = document.getElementById('nav-item-attributes');
    const liVariants = document.getElementById('nav-item-variants');
    
    // Tab buttons
    const tabAttributes = document.getElementById('v-pills-attributes-tab');
    const tabVariants = document.getElementById('v-pills-variants-tab');
    const tabGeneral = document.getElementById('v-pills-general-tab');
    
    const btnAddVariant = document.getElementById('btnAddVariant');
    const btnGenerateVariants = document.getElementById('btnGenerateVariants');
    const variantsAccordion = document.getElementById('variantsAccordion');
    
    const attrSelector = document.getElementById('attrSelector');
    const btnAddAttribute = document.getElementById('btnAddAttribute');
    const productAttributesContainer = document.getElementById('productAttributesContainer');
    
    // Inject PHP data
    const initialVariants = <?= json_encode($item['variants'] ?? []) ?>;
    const initialAttributes = <?= json_encode($item['product_attributes'] ?? []) ?>;
    const allAttributes = <?= json_encode($attributes ?? []) ?>;

    let variantCounter = 0;

    function toggleVariantSection() {
        if (productTypeSelect.value === 'variable') {
            if (liAttributes) liAttributes.style.display = 'block';
            if (liVariants) liVariants.style.display = 'block';
        } else {
            if (liAttributes) liAttributes.style.display = 'none';
            if (liVariants) liVariants.style.display = 'none';
            
            // Nếu đang đứng ở tab Biến thể mà chuyển sang Simple, tự động switch về tab Chung
            if (tabVariants.classList.contains('active') || tabAttributes.classList.contains('active')) {
                if (typeof bootstrap !== 'undefined') {
                    new bootstrap.Tab(tabGeneral).show();
                } else {
                    tabGeneral.click();
                }
            }
        }
    }

    // --- XỬ LÝ THUỘC TÍNH SẢN PHẨM ---
    function getSelectedAttributesData() {
        // Thu thập các thuộc tính đang hiển thị trong UI "Thuộc tính sản phẩm"
        let selectedAttrs = [];
        const attributeRows = productAttributesContainer.querySelectorAll('.attribute-row');
        attributeRows.forEach(row => {
            const attrId = row.dataset.attrId;
            const selectEl = row.querySelector('select');
            const selectedValues = Array.from(selectEl.selectedOptions).map(opt => opt.value);
            if (selectedValues.length > 0) {
                selectedAttrs.push({
                    attrId: attrId,
                    values: selectedValues
                });
            }
        });
        return selectedAttrs;
    }

    function addAttributeRow(attrId, attrName, preselectedValues = []) {
        // Kiểm tra xem thuộc tính này đã thêm chưa
        if (productAttributesContainer.querySelector(`.attribute-row[data-attr-id="${attrId}"]`)) {
            alert('Thuộc tính này đã được thêm!');
            return;
        }

        const attrData = allAttributes.find(a => a.id_code == attrId);
        if (!attrData) return;

        let optionsHtml = '';
        if (attrData.values) {
            attrData.values.forEach(val => {
                const isSelected = preselectedValues.includes(val.id_code.toString()) ? 'selected' : '';
                optionsHtml += `<option value="${val.id_code}" ${isSelected}>${val.title}</option>`;
            });
        }

        const row = document.createElement('div');
        row.className = 'attribute-row row mb-2 align-items-center bg-white p-2 rounded border border-light';
        row.dataset.attrId = attrId;
        
        row.innerHTML = `
            <div class="col-md-3 fw-bold text-primary">
                <i class="fa-solid fa-tag"></i> ${attrName}
            </div>
            <div class="col-md-8">
                <select name="product_attributes[${attrId}][]" class="form-control select2-multiple" multiple="multiple" data-placeholder="Chọn giá trị...">
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attr"><i class="fa-solid fa-times"></i></button>
            </div>
        `;
        productAttributesContainer.appendChild(row);

        // Kích hoạt Select2 nếu đang dùng AdminLTE/Bootstrap
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            jQuery(row.querySelector('.select2-multiple')).select2({ theme: 'bootstrap-5', width: '100%' });
        }
    }

    btnAddAttribute.addEventListener('click', function() {
        const selectedOption = attrSelector.options[attrSelector.selectedIndex];
        if (!selectedOption.value) return;
        addAttributeRow(selectedOption.value, selectedOption.dataset.name);
        attrSelector.value = '';
    });

    productAttributesContainer.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-attr');
        if (btn) {
            if (confirm('Xóa thuộc tính này? (Các biến thể đã sinh ra chứa thuộc tính này sẽ không tự động xóa)')) {
                btn.closest('.attribute-row').remove();
            }
        }
    });

    // --- XỬ LÝ SINH TỔ HỢP TỰ ĐỘNG ---
    // Thuật toán Tích Đề-các (Cartesian Product)
    function cartesianProduct(arrays) {
        return arrays.reduce((acc, curr) => {
            let res = [];
            acc.forEach(a => {
                curr.forEach(b => {
                    res.push(a.concat([b]));
                });
            });
            return res;
        }, [[]]);
    }

    btnGenerateVariants.addEventListener('click', function() {
        const selectedAttrs = getSelectedAttributesData();
        if (selectedAttrs.length === 0) {
            alert('Vui lòng thêm và chọn giá trị cho Thuộc tính Sản phẩm trước khi tạo tổ hợp tự động!');
            return;
        }

        // Tạo mảng 2 chiều chứa các object giá trị (attrId, valueId)
        let arraysToCombine = [];
        selectedAttrs.forEach(item => {
            let valuesArray = item.values.map(valId => ({ attrId: item.attrId, valId: valId }));
            arraysToCombine.push(valuesArray);
        });

        const combinations = cartesianProduct(arraysToCombine);

        if (!confirm(`Hệ thống sẽ tạo ra ${combinations.length} biến thể tự động từ các thuộc tính đã chọn. Bạn có chắc chắn không?`)) {
            return;
        }

        // Xóa sạch biến thể cũ (Tùy chọn: Hoặc giữ lại)
        variantsAccordion.innerHTML = '';
        variantCounter = 0;

        combinations.forEach(combo => {
            // combo là mảng các object: [{attrId: "1", valId: "2"}, {attrId: "3", valId: "5"}]
            let variantAttrs = {};
            combo.forEach(c => {
                variantAttrs[c.attrId] = c.valId;
            });
            
            // Lấy giá mặc định từ SP gốc nếu có
            const defaultPrice = document.querySelector('input[name="price"]').value || 0;
            const defaultPromo = document.querySelector('input[name="promotional_price"]').value || 0;
            const defaultFlash = document.querySelector('input[name="gia_flash_sale"]').value || 0;
            
            addVariantAccordion({
                price: defaultPrice,
                promotional_price: defaultPromo,
                gia_flash_sale: defaultFlash,
                attributes: variantAttrs
            });
        });
    });

    // --- XỬ LÝ ACCORDION BIẾN THỂ ---
    function createAttributeSelects(variantIndex, selectedAttributes) {
        let html = '';
        // CHỈ RENDER NHỮNG THUỘC TÍNH MÀ SP NÀY ĐÃ CHỌN TRONG "THUỘC TÍNH SP"
        const productAttrs = getSelectedAttributesData(); 
        let attrsToRender = [];

        if (productAttrs.length > 0) {
            // Lấy danh sách ID từ UI và Lọc các giá trị đã chọn
            productAttrs.forEach(pa => {
                const attrObj = allAttributes.find(a => a.id_code == pa.attrId);
                if (attrObj) {
                    let newAttrObj = Object.assign({}, attrObj);
                    newAttrObj.values = attrObj.values ? attrObj.values.filter(v => pa.values.includes(v.id_code.toString())) : [];
                    attrsToRender.push(newAttrObj);
                }
            });
        } else {
            // Fallback (khi load lại trang chưa có UI nhưng có dữ liệu khởi tạo, hoặc đang thêm lẻ tẻ)
            if (initialAttributes && Object.keys(initialAttributes).length > 0) {
                Object.keys(initialAttributes).forEach(attrId => {
                    const attrObj = allAttributes.find(a => a.id_code == attrId);
                    if (attrObj) {
                        let newAttrObj = Object.assign({}, attrObj);
                        let selectedValIds = initialAttributes[attrId] || [];
                        newAttrObj.values = attrObj.values ? attrObj.values.filter(v => selectedValIds.includes(v.id_code.toString())) : [];
                        attrsToRender.push(newAttrObj);
                    }
                });
            } else if (selectedAttributes && Object.keys(selectedAttributes).length > 0) {
                // Fallback từ variant data (legacy)
                 Object.keys(selectedAttributes).forEach(attrId => {
                    const attrObj = allAttributes.find(a => a.id_code == attrId);
                    if (attrObj && !attrsToRender.find(x => x.id_code == attrId)) attrsToRender.push(attrObj);
                });
            }
        }

        attrsToRender.forEach(attr => {
            let selectedVal = selectedAttributes ? (selectedAttributes[attr.id_code] || '') : '';
            html += `<div class="col-md-3 mb-2">
                <label class="form-label text-primary fw-bold"><i class="fa-solid fa-tag"></i> ${attr.title}</label>
                <select name="variants[${variantIndex}][attributes][${attr.id_code}]" class="form-select form-select-sm variant-attr-select" data-attr-name="${attr.title}">
                    <option value="">- Chọn -</option>`;
            
            if (attr.values) {
                attr.values.forEach(val => {
                    const isSelected = selectedVal == val.id_code ? 'selected' : '';
                    html += `<option value="${val.id_code}" ${isSelected}>${val.title}</option>`;
                });
            }
            html += `</select></div>`;
        });
        
        if (html === '') {
            html = '<div class="col-12"><small class="text-muted fst-italic">Vui lòng thiết lập "Thuộc tính sản phẩm" ở phía trên trước.</small></div>';
        }
        return html;
    }

    function updateVariantTitle(accordionItem) {
        let titleParts = [];
        accordionItem.querySelectorAll('.variant-attr-select').forEach(select => {
            if (select.value) {
                titleParts.push(select.options[select.selectedIndex].text);
            }
        });
        
        let titleSpan = accordionItem.querySelector('.variant-title');
        if (titleParts.length > 0) {
            titleSpan.innerText = titleParts.join(' - ');
        } else {
            titleSpan.innerText = 'Biến thể chưa cấu hình';
        }

        let skuInput = accordionItem.querySelector('.variant-input-sku');
        let skuBadge = accordionItem.querySelector('.variant-sku');
        if (skuInput && skuInput.value) {
            skuBadge.innerText = 'SKU: ' + skuInput.value;
            skuBadge.style.display = 'inline-block';
        } else {
            skuBadge.style.display = 'none';
        }
        
        let priceInput = accordionItem.querySelector('.variant-input-price');
        let priceBadge = accordionItem.querySelector('.variant-price');
        if (priceInput && priceInput.value) {
            priceBadge.innerText = Number(priceInput.value).toLocaleString('vi-VN') + 'đ';
            priceBadge.style.display = 'inline-block';
        } else {
            priceBadge.style.display = 'none';
        }
    }

    function addVariantAccordion(variant) {
        const index = variantCounter++;
        const id = variant ? (variant.id || 0) : 0;
        const sku = variant ? (variant.sku || '') : '';
        const barcode = variant ? (variant.barcode || '') : '';
        const price = variant ? (variant.price || 0) : 0;
        const promoPrice = variant ? (variant.promotional_price || 0) : 0;
        const flashPrice = variant ? (variant.gia_flash_sale || 0) : 0;
        const stock = variant ? (variant.stock_quantity || 0) : 0;
        const weight = variant ? (variant.weight || 0) : 0;
        const img = variant ? (variant.image || '') : '';
        const attributes = variant ? (variant.attributes || {}) : null;

        const attrHtml = createAttributeSelects(index, attributes);

        const itemDiv = document.createElement('div');
        itemDiv.className = 'accordion-item variant-item';
        itemDiv.style.position = 'relative';
        
        itemDiv.innerHTML = `
            <h2 class="accordion-header" id="heading${index}">
                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="false" aria-controls="collapse${index}">
                    <span class="fw-bold me-2 variant-title">Biến thể #${index + 1}</span>
                    <span class="badge bg-secondary ms-2 variant-sku" style="display:none;"></span>
                    <span class="badge bg-success ms-2 variant-price" style="display:none;"></span>
                </button>
            </h2>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant position-absolute" style="right: 50px; top: 6px; z-index: 5;" title="Xóa biến thể này">
                <i class="fa-solid fa-trash"></i>
            </button>
            <div id="collapse${index}" class="accordion-collapse collapse" aria-labelledby="heading${index}" data-bs-parent="#variantsAccordion">
                <div class="accordion-body bg-white border-top">
                    <input type="hidden" name="variants[${index}][id]" value="${id}">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <div class="border rounded p-1 mb-2 bg-light">
                                <img src="${img ? (img.startsWith('http') || img.startsWith('/') ? img : '/img_data/images/' + img) : 'https://placehold.co/150x150?text=No+Img'}" class="img-fluid rounded" style="width: 100%; aspect-ratio: 1; object-fit: cover;" id="preview_variant-image-${index}">
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="text" name="variants[${index}][image]" id="variant-image-${index}" class="form-control form-control-sm text-center" value="${img}" placeholder="URL Ảnh" onchange="document.getElementById('preview_variant-image-${index}').src = this.value ? (this.value.startsWith('http') || this.value.startsWith('/') ? this.value : '/img_data/images/' + this.value) : 'https://placehold.co/150x150?text=No+Img';">
                                <button class="btn btn-outline-secondary" type="button" onclick="openCKFinder('variant-image-${index}', '/img_data/images/')">
                                    <i class="fa-solid fa-folder-open"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <!-- Các trường cơ bản -->
                            <div class="row bg-light p-2 rounded mb-3">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Mã SKU</label>
                                    <input type="text" name="variants[${index}][sku]" class="form-control form-control-sm variant-input-sku" value="${sku}" placeholder="Ví dụ: SP-D-L">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Mã vạch (Barcode)</label>
                                    <input type="text" name="variants[${index}][barcode]" class="form-control form-control-sm" value="${barcode}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Tồn kho</label>
                                    <input type="number" name="variants[${index}][stock_quantity]" class="form-control form-control-sm" value="${stock}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Trọng lượng (g)</label>
                                    <input type="number" step="0.01" name="variants[${index}][weight]" class="form-control form-control-sm" value="${weight}">
                                </div>
                            </div>
                            
                            <!-- Giá cả -->
                            <div class="row bg-light p-2 rounded mb-3">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Giá bán (VNĐ)</label>
                                    <input type="number" name="variants[${index}][price]" class="form-control form-control-sm variant-input-price text-success fw-bold" value="${price}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Giá khuyến mãi (VNĐ)</label>
                                    <input type="number" name="variants[${index}][promotional_price]" class="form-control form-control-sm text-danger" value="${promoPrice}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label text-muted mb-1">Giá Flash Sale (VNĐ)</label>
                                    <input type="number" name="variants[${index}][gia_flash_sale]" class="form-control form-control-sm text-warning" value="${flashPrice}">
                                </div>
                            </div>

                            <!-- Thuộc tính -->
                            <div class="row p-2 border border-primary rounded border-opacity-25">
                                ${attrHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        variantsAccordion.appendChild(itemDiv);
        
        // Gắn sự kiện để tự động cập nhật Header
        itemDiv.querySelectorAll('.variant-attr-select, .variant-input-sku, .variant-input-price').forEach(input => {
            input.addEventListener('change', () => updateVariantTitle(itemDiv));
            input.addEventListener('keyup', () => updateVariantTitle(itemDiv));
        });
        
        updateVariantTitle(itemDiv);
    }

    productTypeSelect.addEventListener('change', toggleVariantSection);
    
    btnAddVariant.addEventListener('click', function() {
        addVariantAccordion(null);
    });

    variantsAccordion.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-variant');
        if (btn) {
            if (confirm('Bạn có chắc chắn muốn xóa biến thể này?')) {
                btn.closest('.accordion-item').remove();
            }
        }
    });

    // --- KHỞI TẠO DỮ LIỆU BAN ĐẦU KHI VÀO TRANG EDIT ---
    toggleVariantSection();
    
    // 1. Render Thuộc tính SP
    if (initialAttributes && Object.keys(initialAttributes).length > 0) {
        Object.keys(initialAttributes).forEach(attrId => {
            const attrObj = allAttributes.find(a => a.id_code == attrId);
            if (attrObj) {
                addAttributeRow(attrId, attrObj.title, initialAttributes[attrId]);
            }
        });
    }

    // 2. Render Biến thể
    if (initialVariants && initialVariants.length > 0) {
        initialVariants.forEach(v => addVariantAccordion(v));
    }
});
</script>
