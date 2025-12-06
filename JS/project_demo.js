// Auto-submit for customer search/sort
document.addEventListener('DOMContentLoaded', function() {
  var sortSelect = document.getElementById('sortSelect');
  var searchInput = document.getElementById('searchInput');
  var form = document.getElementById('searchSortForm');
  if (sortSelect && form) {
    sortSelect.addEventListener('change', function() {
      form.submit();
    });
  }
  if (searchInput && form) {
    searchInput.addEventListener('input', function() {
      clearTimeout(window.searchTimeout);
      window.searchTimeout = setTimeout(function() {
        form.submit();
      }, 500);
    });
  }
});

let cart = [];
function changeQuantity(btn, delta) {
  const span = btn.parentElement.querySelector("span");
  let qty = parseInt(span.innerText) + delta;
      qty = qty < 1 ? 1 : qty;
      span.innerText = qty;
}

function addToCart(btn) {
  const card = btn.parentElement;
  const name = card.getAttribute("data-name");
  const price = parseInt(card.getAttribute("data-price"));
  const qty = parseInt(card.querySelector(".quantity span").innerText);

  const existing = cart.find(item => item.name === name);
  if (existing) {
     existing.qty += qty;
  } else {
           cart.push({ name, price, qty });
        }

  renderCart();
  }

function removeFromCart(index) {
  cart.splice(index, 1);
  renderCart();
  }

function renderCart() {
  const cartItems = document.getElementById("cart-items");
  const cartTotal = document.getElementById("cart-total");

  cartItems.innerHTML = "";
  let total = 0;

  cart.forEach((item, index) => {
    const li = document.createElement("li");
    const text = document.createElement("span");
    text.textContent = `${item.name} x ${item.qty}`;
    const price = document.createElement("span");
    price.textContent = (item.price * item.qty).toLocaleString() + " đ";
    const removeBtn = document.createElement("button");
    removeBtn.textContent = "❌";
    removeBtn.className = "remove-btn";
    removeBtn.onclick = () => removeFromCart(index);

    li.appendChild(text);
    li.appendChild(price);
    li.appendChild(removeBtn);
    cartItems.appendChild(li);

    total += item.price * item.qty;
  });

  cartTotal.textContent = total.toLocaleString();
}

function openPopup() {
  if (cart.length === 0) {
    alert("Giỏ hàng trống!");
    return;
  }
  
  // Kiểm tra đăng nhập
  if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
    if (confirm("Bạn cần đăng nhập để mua hàng. Bạn có muốn đăng nhập ngay bây giờ?")) {
      // Lưu giỏ hàng vào sessionStorage để giữ lại sau khi đăng nhập
      sessionStorage.setItem('cart', JSON.stringify(cart));
      window.location.href = typeof loginUrl !== 'undefined' ? loginUrl : '../auth/login_customer.php';
    }
    return;
  }
  
  const popup = document.getElementById("checkout-popup");
  const summary = document.getElementById("order-summary");
  summary.innerHTML = "<h4>Chi tiết giỏ hàng:</h4><ul>" +
    cart.map(item => `<li>${item.name} x ${item.qty} = ${(item.price * item.qty).toLocaleString()} đ</li>`).join("") +
    "</ul><p><b>Tổng tiền: " + cart.reduce((t, i) => t + i.price * i.qty, 0).toLocaleString() + " đ</b></p>";
    popup.style.display = "flex";
}

function closePopup() {
  document.getElementById("checkout-popup").style.display = "none";
}

function sendCart() {
  // Chuyển mảng cart[] thành JSON
  document.getElementById("cart_json").value = JSON.stringify(cart);
  return true; // Cho phép form submit
}

// Toggle ẩn/hiện giỏ hàng
function toggleCart() {
  const cartSidebar = document.getElementById("cart-sidebar");
  const cartShowBtn = document.getElementById("cart-show-btn");
  const cartToggle = document.getElementById("cart-toggle");
  
  if (cartSidebar && cartShowBtn && cartToggle) {
    const isHidden = cartSidebar.classList.contains("hidden");
    
    if (isHidden) {
      // Hiển thị giỏ hàng
      cartSidebar.classList.remove("hidden");
      cartShowBtn.style.display = "none";
      cartToggle.textContent = "−";
      cartToggle.title = "Ẩn giỏ hàng";
    } else {
      // Ẩn giỏ hàng
      cartSidebar.classList.add("hidden");
      cartShowBtn.style.display = "flex";
      cartToggle.textContent = "+";
      cartToggle.title = "Hiển thị giỏ hàng";
    }
  }
}


// Image preview handler moved from edit_san_pham.php
document.addEventListener('DOMContentLoaded', function() {
  try {
    const inputFile = document.getElementById('anh_san_pham');
    const previewImg = document.getElementById('previewImg');
    if (!inputFile || !previewImg) return;

    inputFile.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewImg.style.display = "block";
        }
        reader.readAsDataURL(file);
      }
    });
  } catch (e) {
    // Fail silently if DOM elements not present
    console.warn('Preview handler error', e);
  }
});


 