/**
 * Reusable Location Selector
 * Dùng cho các Form chọn Quốc gia -> Tỉnh/Bang -> Quận/Huyện -> Phường/Xã
 * Có thể sử dụng nhiều instance trên cùng 1 trang.
 */
document.addEventListener('DOMContentLoaded', function() {
    const wrappers = document.querySelectorAll('.location-selector-wrapper');

    wrappers.forEach(wrapper => {
        const countrySelect = wrapper.querySelector('.loc-country');
        const provinceSelect = wrapper.querySelector('.loc-province');
        const districtSelect = wrapper.querySelector('.loc-district');
        const wardSelect = wrapper.querySelector('.loc-ward');

        if (!countrySelect || !provinceSelect || !districtSelect || !wardSelect) return;
        
        const csrfToken = wrapper.nextElementSibling && wrapper.nextElementSibling.classList.contains('loc-csrf') 
            ? wrapper.nextElementSibling.value 
            : '';

        const provinceContainer = provinceSelect.closest('.loc-col');
        const districtContainer = districtSelect.closest('.loc-col');
        const wardContainer = wardSelect.closest('.loc-col');

        // Logic khi đổi Quốc gia
        function toggleCountryFields() {
            let countryId = countrySelect.value;
            
            districtSelect.innerHTML = '<option value="0">Tất cả quận huyện</option>';
            wardSelect.innerHTML = '<option value="0">Tất cả phường xã</option>';
            
            if (countryId && countryId !== '0') {
                $.ajax({
                    url: '/admin/location/get-provinces',
                    type: 'POST',
                    data: { country_id: countryId, _token: csrfToken },
                    success: function(res) {
                        provinceSelect.innerHTML = '<option value="0">Tất cả tỉnh thành</option>';
                        if (res && res.length > 0) {
                            res.forEach(function(prov) {
                                let option = document.createElement('option');
                                option.value = prov.id;
                                option.textContent = prov.name;
                                provinceSelect.appendChild(option);
                            });
                            provinceContainer.style.display = 'block';
                        } else {
                            provinceContainer.style.display = 'none';
                            districtContainer.style.display = 'none';
                            wardContainer.style.display = 'none';
                        }
                    }
                });
            } else {
                provinceContainer.style.display = 'none';
                districtContainer.style.display = 'none';
                wardContainer.style.display = 'none';
            }
        }

        $(countrySelect).on('change', toggleCountryFields);

        // Khởi tạo hiển thị Tỉnh lúc load trang
        if (provinceSelect.options.length <= 1 && countrySelect.value !== '0') {
            toggleCountryFields();
        }

        // Logic khi đổi Tỉnh/Bang
        $(provinceSelect).on('change', function() {
            let provinceId = this.value;
            
            districtSelect.innerHTML = '<option value="0">Tất cả quận huyện</option>';
            wardSelect.innerHTML = '<option value="0">Tất cả phường xã</option>';
            
            if (provinceId && provinceId !== '0') {
                $.ajax({
                    url: '/admin/location/get-districts',
                    type: 'POST',
                    data: { province_id: provinceId, _token: csrfToken },
                    success: function(res) {
                        if (res && res.length > 0) {
                            res.forEach(function(dist) {
                                let option = document.createElement('option');
                                option.value = dist.id;
                                option.textContent = dist.name;
                                districtSelect.appendChild(option);
                            });
                            districtContainer.style.display = 'block';
                        } else {
                            districtContainer.style.display = 'none';
                            wardContainer.style.display = 'none';
                        }
                    }
                });
            } else {
                districtContainer.style.display = 'none';
                wardContainer.style.display = 'none';
            }
        });

        // Khởi tạo hiển thị Xã lúc load trang
        // PHP đã tự xử lý ẩn nếu không có dữ liệu
        
        // Logic khi đổi Quận/Huyện
        $(districtSelect).on('change', function() {
            let districtId = this.value;
            wardSelect.innerHTML = '<option value="0">Tất cả phường xã</option>';
            
            if (districtId && districtId !== '0') {
                $.ajax({
                    url: '/admin/location/get-wards',
                    type: 'POST',
                    data: { district_id: districtId, _token: csrfToken },
                    success: function(res) {
                        if (res && res.length > 0) {
                            res.forEach(function(ward) {
                                let option = document.createElement('option');
                                option.value = ward.id;
                                option.textContent = ward.name;
                                wardSelect.appendChild(option);
                            });
                            wardContainer.style.display = 'block';
                        } else {
                            wardContainer.style.display = 'none';
                        }
                    }
                });
            } else {
                wardContainer.style.display = 'none';
            }
        });

        // Khởi tạo hiển thị Xã lúc load trang
        // PHP đã tự xử lý ẩn nếu không có dữ liệu
    });
});
