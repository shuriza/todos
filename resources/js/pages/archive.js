/**
 * ============================================
 * Archive Page — archivePageApp()
 * ============================================
 * Halaman: archive/index.blade.php
 * Fitur: buka kembali tugas terarsip (completed / unfinished) ke daftar aktif.
 * Data di-render server-side via Blade. JS hanya handle aksi "Buka Kembali".
 */

import { apiHeaders, confirmDialog, toast } from '../helpers';

window.archivePageApp = function () {
    return {
        // Kembalikan tugas terarsip ke status aktif ('todo').
        // Dipakai untuk membatalkan tugas yang salah ditandai
        // Selesai / Tidak Terselesaikan.
        async reopenTask(id) {
            if (!await confirmDialog({
                title: 'Buka Kembali Tugas',
                message: 'Tugas akan dikembalikan ke daftar tugas aktif dan keluar dari arsip.',
                confirmText: 'Buka Kembali',
                variant: 'warning',
            })) return;

            try {
                const res = await fetch(`/todos/${id}`, {
                    method: 'PUT',
                    headers: apiHeaders(),
                    body: JSON.stringify({ status: 'todo' }),
                });
                const data = await res.json();
                if (data.success) {
                    toast('Tugas dibuka kembali ke daftar aktif');
                    const row = document.getElementById(`archive-row-${id}`);
                    if (row) {
                        row.style.transition = 'opacity 0.2s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 200);
                    }
                } else {
                    toast(data.message || 'Gagal membuka kembali tugas', 'error');
                }
            } catch {
                toast('Gagal membuka kembali tugas', 'error');
            }
        },
    };
};
