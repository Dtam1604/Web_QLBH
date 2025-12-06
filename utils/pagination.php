<?php
/**
 * Helper function để render pagination links
 * 
 * @param int $currentPage Trang hiện tại
 * @param int $totalPages Tổng số trang
 * @param string $baseUrl URL cơ sở (không có page parameter)
 * @return string HTML pagination
 */
function renderPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    // Xây dựng query string (giữ lại search, sort, etc.)
    $queryParams = $_GET;
    unset($queryParams['page']);
    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
    
    $html = '<div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 8px;">';
    
    // Nút Previous
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<a href="' . htmlspecialchars($baseUrl . '?page=' . $prevPage . $queryString) . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1976d2; background: #fff;">‹ Trước</a>';
    } else {
        $html .= '<span class="pagination-btn disabled" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; color: #999; background: #f5f5f5; cursor: not-allowed;">‹ Trước</span>';
    }
    
    // Số trang
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<a href="' . htmlspecialchars($baseUrl . '?page=1' . $queryString) . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1976d2; background: #fff;">1</a>';
        if ($startPage > 2) {
            $html .= '<span style="padding: 8px 4px; color: #666;">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="pagination-btn active" style="padding: 8px 12px; border: 1px solid #1976d2; border-radius: 4px; color: #fff; background: #1976d2; font-weight: bold;">' . $i . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($baseUrl . '?page=' . $i . $queryString) . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1976d2; background: #fff;">' . $i . '</a>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span style="padding: 8px 4px; color: #666;">...</span>';
        }
        $html .= '<a href="' . htmlspecialchars($baseUrl . '?page=' . $totalPages . $queryString) . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1976d2; background: #fff;">' . $totalPages . '</a>';
    }
    
    // Nút Next
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<a href="' . htmlspecialchars($baseUrl . '?page=' . $nextPage . $queryString) . '" class="pagination-btn" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1976d2; background: #fff;">Sau ›</a>';
    } else {
        $html .= '<span class="pagination-btn disabled" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; color: #999; background: #f5f5f5; cursor: not-allowed;">Sau ›</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

