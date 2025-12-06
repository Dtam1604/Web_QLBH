/**
 * Admin Tabs - Quản lý tab trong trang admin
 */
function showTab(tab) {
  // Hide all tab contents
  document.querySelectorAll('.tab-content').forEach(content => {
    content.style.display = 'none';
  });
  
  // Remove active class from all buttons
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.style.background = '#e0e0e0';
    btn.style.color = '#333';
  });
  
  // Show selected tab content
  document.getElementById('tab-content-' + tab).style.display = 'block';
  
  // Add active class to selected button
  const activeBtn = document.getElementById('tab-' + tab);
  activeBtn.style.background = '#1976d2';
  activeBtn.style.color = 'white';
}

