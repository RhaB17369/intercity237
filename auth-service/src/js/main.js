/* ============================================================
   CIMENCAM HR Portal — Main JavaScript
   ============================================================ */

/* ---- Scroll Progress Bar ---- */
(function initScrollProgress() {
    const bar = document.getElementById('scroll-progress');
    if (!bar) return;
    const update = () => {
        const scrolled = window.scrollY;
        const total    = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = total > 0 ? (scrolled / total * 100) + '%' : '0%';
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
})();


/* ---- Navbar: transparent ↔ solid on scroll ---- */
(function initNavbar() {
    const nav    = document.getElementById('mainNav');
    const hero   = document.getElementById('hero');
    if (!nav) return;

    const THRESHOLD = 80;

    const tick = () => {
        if (hero) {
            if (window.scrollY < THRESHOLD) {
                nav.classList.add('nav-hero');
                nav.classList.remove('nav-scrolled');
            } else {
                nav.classList.remove('nav-hero');
                nav.classList.add('nav-scrolled');
            }
        } else {
            nav.classList.remove('nav-hero');
            nav.classList.add('nav-scrolled');
        }
    };

    window.addEventListener('scroll', tick, { passive: true });
    tick();
})();


/* ---- Hero parallax on scroll ---- */
(function initParallax() {
    const bg = document.getElementById('heroBg');
    if (!bg) return;

    const move = () => {
        const y = window.scrollY;
        bg.style.transform = 'translateY(' + (y * 0.35) + 'px)';
    };

    window.addEventListener('scroll', move, { passive: true });
})();


/* ---- Reveal animations via IntersectionObserver ---- */
(function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!items.length) return;

    /* Trigger hero items immediately */
    const heroItems = document.querySelectorAll('.cim-hero .reveal');
    setTimeout(() => {
        heroItems.forEach(el => el.classList.add('visible'));
    }, 80);

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    observer.unobserve(e.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    items.forEach(el => {
        if (!el.closest('.cim-hero')) {
            observer.observe(el);
        }
    });
})();


/* ---- Animated counters ---- */
(function initCounters() {
    const counters = document.querySelectorAll('.js-counter');
    if (!counters.length) return;

    const easeOut = t => 1 - Math.pow(1 - t, 3);

    const animateCounter = (el) => {
        const target   = parseInt(el.dataset.target) || 0;
        const duration = 1800;
        const start    = performance.now();

        const step = (now) => {
            const elapsed  = now - start;
            const progress = Math.min(elapsed / duration, 1);
            el.textContent = Math.floor(easeOut(progress) * target);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        };

        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    animateCounter(e.target);
                    observer.unobserve(e.target);
                }
            });
        },
        { threshold: 0.5 }
    );

    counters.forEach(el => observer.observe(el));
})();


/* ---- Auto-dismiss alerts ---- */
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        document.querySelectorAll('.auto-dismiss').forEach(el => {
            try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch (e) {}
        });
    }, 4500);

    /* Password strength indicator */
    const pwField    = document.getElementById('password');
    const pwStrength = document.getElementById('pw-strength');
    if (pwField && pwStrength) {
        pwField.addEventListener('input', function () {
            const pw = this.value;
            let score = 0;
            if (pw.length >= 8)         score++;
            if (/[A-Z]/.test(pw))       score++;
            if (/[0-9]/.test(pw))       score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', 'danger', 'warning', 'info', 'success'];
            pwStrength.className = 'text-' + (colors[score] || 'muted') + ' small';
            pwStrength.textContent = score > 0 ? '● ' + levels[score] : '';
        });
    }
});


/* ---- Password toggle ---- */
function togglePw(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}


/* ---- Delete confirmation ---- */
function confirmDelete(url, itemName) {
    if (confirm('Delete "' + itemName + '"?\nThis action cannot be undone.')) {
        window.location.href = url;
    }
}


/* ---- PDF Export ---- */
function exportTableToPDF(tableId, filename, title, deptName) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    doc.setFillColor(15, 15, 19);
    doc.rect(0, 0, 297, 30, 'F');
    doc.setTextColor(232, 93, 4);
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('CIMENCAM', 14, 13);
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Human Resources Portal', 14, 20);
    doc.setFontSize(12);
    doc.text(title + (deptName ? ' — ' + deptName : ''), 14, 27);
    doc.setTextColor(200, 200, 200);
    doc.setFontSize(9);
    doc.text('Generated: ' + new Date().toLocaleString(), 297 - 14, 27, { align: 'right' });

    const table = document.getElementById(tableId);
    if (!table) { alert('Table not found.'); return; }

    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        if (!th.classList.contains('no-export')) headers.push(th.innerText.trim());
    });

    const rows = [];
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td:not(.no-export)').forEach(td => row.push(td.innerText.trim()));
        if (row.length > 0) rows.push(row);
    });

    if (rows.length === 0) { alert('No data to export.'); return; }

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 35,
        styles: { fontSize: 8, cellPadding: 3 },
        headStyles: { fillColor: [26, 26, 36], textColor: [255, 255, 255], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [250, 248, 245] },
        columnStyles: { 0: { cellWidth: 18 } }
    });

    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setTextColor(150);
        doc.setFontSize(8);
        doc.text('CIMENCAM Confidential — HR Portal', 14, 205);
        doc.text('Page ' + i + ' / ' + pageCount, 297 - 14, 205, { align: 'right' });
    }

    doc.save(filename + '_' + new Date().toISOString().slice(0, 10) + '.pdf');
}
