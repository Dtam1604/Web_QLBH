/**
 * Cart Restore - Khôi phục giỏ hàng từ sessionStorage sau khi đăng nhập
 */
(function() {
  'use strict';
  
  // Khôi phục giỏ hàng từ sessionStorage nếu có (sau khi đăng nhập)
  document.addEventListener('DOMContentLoaded', function() {
    const savedCart = sessionStorage.getItem('cart');
    if (savedCart && typeof cart !== 'undefined') {
      try {
        const parsedCart = JSON.parse(savedCart);
        if (Array.isArray(parsedCart) && parsedCart.length > 0) {
          cart = parsedCart;
          if (typeof renderCart === 'function') {
            renderCart();
          }
          sessionStorage.removeItem('cart');
        }
      } catch(e) {
        console.warn('Không thể khôi phục giỏ hàng:', e);
      }
    }
  });
})();

