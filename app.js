document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.verify-page')) return;

    const otpInputs = document.querySelectorAll('.otp-input');
    const verifyForm = document.getElementById('verifyForm');
    const timerElement = document.getElementById('timer');
    const resendLink = document.getElementById('resendOtp');
    const messageContainer = document.getElementById('verification-message');

    // Khởi tạo thời gian
    const initialExpire = parseInt(timerElement.dataset.expire) || 0;
    let timeLeft = Math.max(Math.floor(initialExpire - Date.now()/1000), 0);

    // Xử lý nhập OTP
    const handleOtpInput = (e, index) => {
        e.target.value = e.target.value.replace(/\D/g, '');

        // Tự động chuyển focus
        if (e.target.value.length === 1 && index < 5) {
            otpInputs[index + 1].focus();
        }
    };

    // Gắn sự kiện input và keydown cho từng ô OTP
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => handleOtpInput(e, index));
        input.addEventListener('keydown', (e) => {
            // Xử lý Backspace
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Bộ đếm ngược
    const startTimer = () => {
        const timerInterval = setInterval(() => {
            timeLeft = Math.max(timeLeft - 1, 0);

            const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const seconds = (timeLeft % 60).toString().padStart(2, '0');
            timerElement.textContent = `${minutes}:${seconds}`;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                resendLink.style.display = 'inline-block';
            }
        }, 1000);
    };

    // Xử lý submit form
    const handleSubmit = async (e) => {
        e.preventDefault();

        // Validate input
        const otpCode = Array.from(otpInputs).map(i => i.value).join('');
        if (otpCode.length !== 6) {
            showMessage('Vui lòng nhập đủ 6 chữ số', 'error');
            return;
        }

        try {
            const response = await fetch('verify_process.php', {
                method: 'POST',
                body: new FormData(verifyForm)
            });

            const result = await response.json();

            if (result.status === 'success') {
                showMessage(result.message, 'success');
                setTimeout(() => window.location.href = result.redirect, 1500);
            } else {
                showMessage(result.message, 'error');
                otpInputs.forEach(i => i.value = '');
                otpInputs[0].focus();
            }
        } catch (error) {
            showMessage('Lỗi kết nối server', 'error');
        }
    };

    // Hiển thị thông báo
    const showMessage = (message, type) => {
        messageContainer.innerHTML = `
            <div class="alert alert-${type}">
                ${message}
            </div>
        `;
        setTimeout(() => messageContainer.innerHTML = '', 3000);
    };

    // Gắn sự kiện submit cho form xác thực
    verifyForm.addEventListener('submit', handleSubmit);
    // Bắt đầu bộ đếm thời gian
    startTimer();
});

// Autocomplete
document.addEventListener('DOMContentLoaded', () => {
    // Hàm khởi tạo tính năng tự động hoàn thành
    const initAutocomplete = () => {
        const destinationInput = document.getElementById('destination');
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'autocomplete-suggestions';
        destinationInput.parentNode.appendChild(suggestionsContainer);

        let timeoutId;

        // Hàm hiển thị các gợi ý
        const showSuggestions = (items) => {
            suggestionsContainer.innerHTML = items
                .map(item => `<div class="suggestion-item">${item}</div>`)
                .join('');

            suggestionsContainer.style.display = items.length ? 'block' : 'none';
        };

        // Lắng nghe sự kiện nhập liệu vào ô input địa điểm
        destinationInput.addEventListener('input', async (e) => {
            clearTimeout(timeoutId);
            const query = e.target.value.trim();

            // Nếu độ dài truy vấn nhỏ hơn 2 ký tự, ẩn gợi ý và thoát
            if(query.length < 2) {
                showSuggestions([]);
                return;
            }

            // Thiết lập timeout để gọi API sau một khoảng thời gian ngắn (tránh gọi liên tục khi gõ)
            timeoutId = setTimeout(async () => {
                try {
                    const response = await fetch(`api/autocomplete.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    showSuggestions(data);
                } catch(error) {
                    console.error('Lỗi tự động hoàn thành:', error);
                }
            }, 300);
        });

        // Xử lý sự kiện click vào một gợi ý
        suggestionsContainer.addEventListener('click', (e) => {
            if(e.target.classList.contains('suggestion-item')) {
                destinationInput.value = e.target.textContent;
                suggestionsContainer.style.display = 'none';
            }
        });

        // Ẩn gợi ý khi click ra ngoài ô input
        document.addEventListener('click', (e) => {
            if(!destinationInput.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    };

    // Hàm kiểm tra và thiết lập ràng buộc cho các ô chọn ngày
    const validateDates = () => {
        const checkin = document.getElementById('checkin');
        const checkout = document.getElementById('checkout');

        // Đặt ngày tối thiểu cho ngày nhận phòng là ngày hiện tại
        checkin.min = new Date().toISOString().split('T')[0];

        // Lắng nghe sự kiện thay đổi ngày nhận phòng
        checkin.addEventListener('change', () => {
            const checkinDate = new Date(checkin.value);
            // Đặt ngày tối thiểu cho ngày trả phòng là ngày sau ngày nhận phòng
            checkinDate.setDate(checkinDate.getDate() + 1);
            checkout.min = checkinDate.toISOString().split('T')[0];

            // Nếu ngày trả phòng được chọn trước ngày tối thiểu, đặt lại giá trị
            if(new Date(checkout.value) < checkinDate) {
                checkout.value = checkout.min;
            }
        });
    };

    // Khởi tạo các tính năng nếu phần tử tồn tại trên trang
    if(document.getElementById('destination')) {
        initAutocomplete();
        validateDates();
    }
});

/* Thêm script xử lý cho booking.php */
document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;

    bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(bookingForm);
        const messageContainer = document.getElementById('booking-message');
        messageContainer.innerHTML = ''; // Clear message

        try {
            const response = await fetch('payment.php', {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const text = await response.text();
            messageContainer.innerHTML = text;

        } catch (error) {
            messageContainer.innerHTML = `<div class="error">Lỗi: ${error.message}</div>`;
        }
    });
});
// app.js - Phần xử lý thanh toán
document.addEventListener('DOMContentLoaded', () => {
    const paymentForm = document.getElementById('paymentForm');
    const resultContainer = document.getElementById('paymentResult');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            resultContainer.style.display = 'block';
            resultContainer.innerHTML = `
                <div class="alert alert-loading">
                    <i class="fas fa-spinner fa-spin"></i> Đang xử lý thanh toán...
                </div>
            `;

            try {
                const response = await fetch('payment_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_method: document.querySelector('.method-btn.active').dataset.method,
                        ...Object.fromEntries(new FormData(paymentForm))
                    })
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    resultContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> ${result.message}
                        </div>
                    `;
                    window.location.href = result.redirect;
                } else {
                    resultContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                resultContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Lỗi kết nối!
                    </div>
                `;
            }
        });
    }
});