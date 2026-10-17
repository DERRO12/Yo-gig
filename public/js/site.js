/* YO'gig Maid - small progressive enhancements, no build step required. */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        /* ---------- Mobile navigation ---------- */
        var toggle = document.querySelector('[data-nav-toggle]');
        var menu = document.querySelector('[data-nav-menu]');

        function setNav(open) {
            if (!toggle || !menu) return;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            menu.classList.toggle('is-open', open);
            document.body.classList.toggle('nav-open', open && window.innerWidth < 960);
        }

        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                setNav(toggle.getAttribute('aria-expanded') !== 'true');
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 960) setNav(false);
            });
        }

        /* ---------- Dropdowns (user menu, admin) ---------- */
        document.querySelectorAll('[data-dropdown]').forEach(function (dropdown) {
            var trigger = dropdown.querySelector('[data-dropdown-trigger]');
            if (!trigger) return;
            trigger.addEventListener('click', function (event) {
                event.stopPropagation();
                var open = dropdown.classList.toggle('is-open');
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function (event) {
            document.querySelectorAll('[data-dropdown].is-open').forEach(function (dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('is-open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            setNav(false);
            document.querySelectorAll('[data-dropdown].is-open').forEach(function (d) { d.classList.remove('is-open'); });
            document.body.classList.remove('sidebar-open');
        });

        /* ---------- Admin sidebar ---------- */
        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-open');
            });
        });

        /* ---------- Hero slider ---------- */
        var slider = document.querySelector('[data-slider]');
        if (slider) {
            var slides = slider.querySelectorAll('[data-slide]');
            var dots = slider.querySelectorAll('[data-slide-to]');
            var current = 0;
            var timer = null;

            var show = function (index) {
                current = (index + slides.length) % slides.length;
                slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === current); });
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === current);
                    dot.setAttribute('aria-selected', i === current ? 'true' : 'false');
                });
            };

            var start = function () {
                if (slides.length < 2 || timer) return;
                timer = setInterval(function () { show(current + 1); }, 6000);
            };

            var stop = function () {
                clearInterval(timer);
                timer = null;
            };

            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    stop();
                    show(parseInt(dot.getAttribute('data-slide-to'), 10));
                    start();
                });
            });

            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            show(0);
            start();
        }

        /* ---------- Segmented tabs (register form) ---------- */
        document.querySelectorAll('[data-tabs]').forEach(function (group) {
            var buttons = group.querySelectorAll('[data-tab]');
            var panels = document.querySelectorAll('[data-tab-panel]');

            var activate = function (name) {
                buttons.forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-tab') === name); });
                panels.forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-tab-panel') === name); });
            };

            buttons.forEach(function (button) {
                button.addEventListener('click', function () { activate(button.getAttribute('data-tab')); });
            });

            if (group.getAttribute('data-tabs')) activate(group.getAttribute('data-tabs'));
        });

        /* ---------- Image previews for file inputs ---------- */
        document.querySelectorAll('[data-preview]').forEach(function (input) {
            var target = document.getElementById(input.getAttribute('data-preview'));
            if (!target) return;
            input.addEventListener('change', function () {
                target.innerHTML = '';
                Array.prototype.slice.call(input.files, 0, 5).forEach(function (file) {
                    if (!file.type.match(/^image\//)) return;
                    var img = document.createElement('img');
                    img.alt = '';
                    img.src = URL.createObjectURL(file);
                    target.appendChild(img);
                });
            });
        });

        /* ---------- Booking price calculator ---------- */
        var bookingForm = document.querySelector('[data-booking-form]');
        if (bookingForm) {
            var currency = bookingForm.getAttribute('data-currency') || '';
            var quantity = bookingForm.querySelector('[name="quantity"]');
            var budgetGroup = bookingForm.querySelector('[data-budget-group]');
            var budget = bookingForm.querySelector('[name="budget"]');
            var unitLabel = bookingForm.querySelector('[data-unit-label]');
            var total = bookingForm.querySelector('[data-total]');

            var format = function (amount) {
                return currency + ' ' + Math.round(amount).toLocaleString();
            };

            var update = function () {
                var selected = bookingForm.querySelector('[name="service_id"]:checked');
                if (!selected) {
                    total.textContent = format(0);
                    return;
                }
                var price = parseFloat(selected.getAttribute('data-price')) || 0;
                var unit = selected.getAttribute('data-unit') || '';
                var qty = Math.max(1, parseInt(quantity.value, 10) || 1);

                if (unitLabel) unitLabel.textContent = unit ? '(' + unit + ')' : '';

                if (price > 0) {
                    budgetGroup.hidden = true;
                    total.textContent = format(price * qty);
                } else {
                    budgetGroup.hidden = false;
                    total.textContent = format(parseFloat(budget.value) || 0);
                }
            };

            bookingForm.addEventListener('change', update);
            bookingForm.addEventListener('input', update);
            update();
        }

        /* ---------- Payment method helper ---------- */
        document.querySelectorAll('[data-payment-method]').forEach(function (select) {
            var phoneGroup = document.querySelector('[data-payer-phone]');
            var sync = function () {
                if (phoneGroup) phoneGroup.hidden = select.value === 'bank_transfer';
            };
            select.addEventListener('change', sync);
            sync();
        });

        /* ---------- Confirm before destructive actions ---------- */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    });
})();
