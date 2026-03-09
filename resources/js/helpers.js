/**
 * ============================================
 * Shared Utility Functions
 * ============================================
 * Helper functions yang digunakan di berbagai halaman.
 * Import dari file page masing-masing.
 */

/**
 * Ambil CSRF token dari meta tag
 */
export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Standard headers untuk fetch API
 * @param {boolean} includeContentType - Sertakan Content-Type JSON
 */
export function apiHeaders(includeContentType = true) {
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
    };
    if (includeContentType) {
        headers['Content-Type'] = 'application/json';
    }
    return headers;
}

/**
 * Format tanggal ke locale Indonesia (singkat)
 * Output: "10 Jan 2025"
 */
export function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/**
 * Format tanggal ke locale Indonesia (lengkap)
 * Output: "Senin, 10 Januari 2025"
 */
export function formatDateFull(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/**
 * Format tanggal + waktu ke locale Indonesia
 * Output: "10 Jan 2025, 14:30"
 */
export function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Label kuadran Eisenhower (panjang)
 * @param {number} k - Nomor kuadran (1-4)
 */
export function getKuadranLabel(k) {
    return {
        1: 'Q1 — DO NOW',
        2: 'Q2 — SCHEDULE',
        3: 'Q3 — DELEGATE',
        4: 'Q4 — ELIMINATE',
    }[k] || '-';
}

/**
 * Label kuadran Eisenhower (singkat)
 */
export function getKuadranShort(k) {
    return { 1: 'Q1', 2: 'Q2', 3: 'Q3', 4: 'Q4' }[k] || '-';
}

/**
 * CSS class untuk badge kuadran
 */
export function getKuadranBadgeClass(k) {
    return {
        1: 'bg-red-100 text-red-700',
        2: 'bg-blue-100 text-blue-700',
        3: 'bg-yellow-100 text-yellow-700',
        4: 'bg-gray-100 text-gray-600',
    }[k] || 'bg-gray-50 text-gray-400';
}

/**
 * CSS class untuk dot kuadran
 */
export function getKuadranDotClass(k) {
    return {
        1: 'bg-red-500',
        2: 'bg-blue-500',
        3: 'bg-yellow-500',
        4: 'bg-gray-400',
    }[k] || 'bg-gray-300';
}

/**
 * Baca data JSON dari <script type="application/json"> block
 * @param {string} elementId - ID elemen script data
 */
export function readJsonData(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}
