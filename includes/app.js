/**
 * MUJERES VIRTUOSAS S.A - Sistema de Gestión
 * JavaScript Principal
 * @version 1.0
 */

// ============================================
// CONFIGURACIÓN GLOBAL
// ============================================

const App = {
    init() {
        this.setupEventListeners();
        this.disableImageDragging();
        this.initAnimations();
        this.animateNumbers();
        this.setupFormValidation();
        this.initTooltips(); 
        this.setupTableEnhancements(); 
    },

    disableImageDragging() {
        document.querySelectorAll('img').forEach(img => {
            img.setAttribute('draggable', 'false');
        });

        // Evitar drag del link del logo (algunos navegadores inician drag en <a> en lugar de <img>)
        document.querySelectorAll('a.navbar-brand').forEach(a => {
            a.setAttribute('draggable', 'false');
        });

        document.addEventListener('dragstart', (e) => {
            const target = e.target;
            if (!target) return;

            if (target.tagName === 'IMG') {
                e.preventDefault();
                return;
            }

            const navbarBrand = target.closest ? target.closest('a.navbar-brand') : null;
            if (navbarBrand) {
                e.preventDefault();
            }
        }, true);
    },

    // ============================================
    // LISTENERS DE EVENTOS
    // ============================================

    setupEventListeners() {
        // Smooth scroll para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Cerrar alerts automáticamente después de 5 segundos
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Confirmaciones centralizadas (evita duplicar confirm() inline)
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', function (e) {
                if (this.getAttribute('data-confirmed') === 'true') return;

                const message = this.getAttribute('data-confirm') || '¿Estás seguro de continuar?';
                e.preventDefault();

                if (App.showConfirmDialog(message)) {
                    this.setAttribute('data-confirmed', 'true');
                    this.click();
                }
            });
        });
    },

    // ============================================
    // ANIMACIONES
    // ============================================

    initAnimations() {
        // Animar elementos cuando entran en viewport
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observar cards y elementos importantes
        // .stat-card solo se usa en dashboard de estadísticas. Si no tienes dashboard, puedes ignorar este selector.
        document.querySelectorAll('.card, .stat-card, .alert').forEach(el => {
            observer.observe(el);
        });

        // Animar números (contador)
        this.animateNumbers(); // Animación de números en dashboard (solo útil si usas .stat-value en HTML)
    },

    
    // Animación de números en dashboard de estadísticas
    // Requiere elementos con clase .stat-value en el HTML
    animateNumbers() {
        document.querySelectorAll('.stat-value').forEach(element => {
            const target = parseInt(element.textContent.replace(/[^0-9]/g, ''));
            if (!isNaN(target) && target > 0) {
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        element.textContent = target.toLocaleString('es-AR');
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(current).toLocaleString('es-AR');
                    }
                }, 30);
            }
        });
    },
    

    // ============================================
    // VALIDACIÓN DE FORMULARIOS
    // ============================================
    // Validación avanzada de formularios
    // Requiere formularios con atributos específicos y clases de Bootstrap
    setupFormValidation() {
        // Validación en tiempo real
        document.querySelectorAll('input[type="text"], input[type="tel"], input[type="number"]').forEach(input => {
            input.addEventListener('input', function () {
                this.classList.remove('is-invalid');
                if (this.validity.valid) {
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
            });

            input.addEventListener('blur', function () {
                if (!this.validity.valid) {
                    this.classList.add('is-invalid');
                    // Ya no se llama a reportValidity aquí
                }
            });
        });

        // Prevenir caracteres no permitidos en teléfono
        document.querySelectorAll('input[type="tel"], input[inputmode="numeric"]').forEach(input => {
            input.addEventListener('keypress', function (e) {
                if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                    e.preventDefault();
                }
            });
        });

        // Formatear números con separadores de miles
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('blur', function () {
                if (this.value) {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        const formatted = value.toLocaleString('es-AR');
                        this.setAttribute('data-formatted', formatted);
                    }
                }
            });
        });
    },


    // ============================================
    // TOOLTIPS
    // ============================================
    // Inicializar tooltips de Bootstrap si existen
    // Útil si usas tooltips en el HTML
    initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                new bootstrap.Tooltip(el);
            });
        }
    },


    // ============================================
    // MEJORAS DE TABLAS
    // ============================================
    // Mejora tablas con búsqueda y paginación
    // Requiere tablas con clase .table-enhanced y un input .table-search en el HTML
    setupTableEnhancements() {
        // Resaltar fila al hacer hover
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(37, 99, 235, 0.08)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Búsqueda en tablas (si existe input de búsqueda)
        const searchInput = document.querySelector('input[name="buscar"]');
        if (searchInput) {
            searchInput.addEventListener('input', App.debounce(function() {
                // Guardar el tab activo antes de buscar
                const activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
                const activeTarget = activeTab ? activeTab.getAttribute('data-bs-target') : null;

                // Búsqueda en tiempo real con AJAX
                const value = this.value;
                const url = new URL(window.location.href);
                url.searchParams.set('buscar', value);
                // Mantener otros filtros si existen
                document.querySelectorAll('#formBusqueda input, #formBusqueda select').forEach(el => {
                    if (el.name !== 'buscar' && el.value) {
                        url.searchParams.set(el.name, el.value);
                    }
                });
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.text())
                    .then(html => {
                        // Reemplazar solo la grilla de resultados
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nuevaTabla = doc.querySelector('#grilla-compras');
                        const actualTabla = document.querySelector('#grilla-compras');
                        if (nuevaTabla && actualTabla) {
                            actualTabla.innerHTML = nuevaTabla.innerHTML;
                        }
                        // Restaurar el tab activo
                        if (activeTarget) {
                            const tabBtn = document.querySelector(`button[data-bs-target='${activeTarget}']`);
                            if (tabBtn) tabBtn.click();
                        }
                    });
            }, 300));
        }


    },

    // ============================================
    // UTILIDADES
    // ============================================
    showConfirmDialog(message) {
        return confirm(message);
    },

    showLoading() {
        let overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(overlay);
        }
        setTimeout(() => overlay.classList.add('active'), 10);
    },

    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    },

    showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="me-3">
                    ${type === 'success'
                        ? '<i class="bi bi-check-circle-fill"></i>'
                        : type === 'error'
                            ? '<i class="bi bi-x-circle-fill"></i>'
                            : '<i class="bi bi-exclamation-triangle-fill"></i>'}
                </div>
                <div>${message}</div>
            </div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // ============================================
    // FORMATEO DE DATOS
    // ============================================

    formatCurrency(amount) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 0
        }).format(amount);
    },

    formatDate(date) {
        return new Intl.DateTimeFormat('es-AR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }
};

// ============================================
// FUNCIONES ESPECÍFICAS DEL FORMULARIO
// ============================================

function calcularMontoCuota() {
    const valorTotal = parseFloat(document.getElementById('valor_total')?.value) || 0;
    const sena = parseFloat(document.getElementById('sena')?.value) || 0;
    const cuotas = parseInt(document.getElementById('cuotas')?.value) || 1;

    if (sena > valorTotal) {
        document.getElementById('sena').value = valorTotal;
        App.showToast('La seña no puede ser mayor al valor total', 'warning');
        return;
    }

    const saldoRestante = valorTotal - sena;

    if (saldoRestante > 0 && cuotas > 0) {
        const montoCuota = saldoRestante / cuotas;
        const montoCuotaEl = document.getElementById('monto-cuota');
        if (montoCuotaEl) {
            montoCuotaEl.textContent = App.formatCurrency(montoCuota);
            document.getElementById('info-cuota').style.display = 'block';
        }
    }
}

// ============================================
// SISTEMA DE DROPDOWN MEJORADO
// ============================================

const DropdownManager = {
    init() {
        this.setupDropdowns();
        this.setupClickOutside();
    },

    setupDropdowns() {
        // Obtener todos los botones dropdown
        const dropdownButtons = document.querySelectorAll('[data-bs-toggle="dropdown"]');

        dropdownButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const menu = button.nextElementSibling;
                const isOpen = menu && menu.classList.contains('show');

                // Cerrar todos los dropdowns abiertos
                this.closeAllDropdowns();

                // Toggle del dropdown actual (si no estaba abierto, abrirlo)
                if (!isOpen && menu && menu.classList.contains('dropdown-menu')) {
                    this.openDropdown(button, menu);
                }
            });
        });
    },

    setupClickOutside() {
        // Cerrar dropdowns al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                this.closeAllDropdowns();
            }
        });

        // Cerrar dropdowns al presionar ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
            }
        });

        // Cerrar dropdowns al hacer scroll (especialmente en tablas)
        document.addEventListener('scroll', () => {
            this.closeAllDropdowns();
        }, true);

        // Cerrar dropdowns al cambiar el tamaño de ventana
        window.addEventListener('resize', () => {
            this.closeAllDropdowns();
        });
    },

    openDropdown(button, menu) {
        menu.classList.add('show');
        button.setAttribute('aria-expanded', 'true');

        // Posicionar el menú después de que se muestre
        setTimeout(() => {
            this.positionMenu(button, menu);
        }, 10);

        // Animación de entrada
        menu.style.animation = 'slideDown 0.2s ease';
    },

    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
            const button = menu.previousElementSibling;
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
            // Resetear estilos personalizados
            menu.style.top = '';
            menu.style.bottom = '';
            menu.style.left = '';
            menu.style.right = '';
        });
    },

    positionMenu(button, menu) {
        const buttonRect = button.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;

        // Verificar si está dentro de una tabla con scroll
        const isInTableResponsive = button.closest('.table-responsive');

        if (isInTableResponsive) {
            // Posicionamiento fijo para tablas con scroll
            const menuWidth = 180; // ancho mínimo del menú
            const menuHeight = menu.scrollHeight || 200;

            // Calcular posición vertical
            let top = buttonRect.bottom + 8;
            if (top + menuHeight > viewportHeight - 20) {
                top = buttonRect.top - menuHeight - 8;
            }

            // Calcular posición horizontal (siempre a la izquierda del botón)
            let left = buttonRect.right - menuWidth;
            if (left < 10) {
                left = buttonRect.left;
            }

            menu.style.position = 'fixed';
            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
            menu.style.right = 'auto';
            menu.style.bottom = 'auto';
        } else {
            // Posicionamiento normal (absoluto)
            menu.style.position = 'absolute';

            // Resetear
            menu.style.top = '';
            menu.style.bottom = '';
            menu.style.left = '';
            menu.style.right = '';

            const menuRect = menu.getBoundingClientRect();

            // Alinear a la derecha por defecto
            menu.style.right = '0';
            menu.style.left = 'auto';

            // Verificar si se sale por abajo
            if (buttonRect.bottom + menuRect.height > viewportHeight - 20) {
                menu.style.top = 'auto';
                menu.style.bottom = '100%';
                menu.style.marginBottom = '0.5rem';
            } else {
                menu.style.top = '100%';
                menu.style.bottom = 'auto';
                menu.style.marginTop = '0.5rem';
            }
        }
    }
};

// ============================================
// INICIALIZACIÓN AL CARGAR EL DOM
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar aplicación
    App.init();

    // Inicializar sistema de dropdowns
    DropdownManager.init();

    // Eventos específicos del formulario de clientes
    const valorTotal = document.getElementById('valor_total');
    const sena = document.getElementById('sena');
    const cuotas = document.getElementById('cuotas');

    if (valorTotal) valorTotal.addEventListener('input', calcularMontoCuota);
    if (sena) sena.addEventListener('input', calcularMontoCuota);
    if (cuotas) cuotas.addEventListener('input', calcularMontoCuota);

    // Prevenir envío de formularios múltiples veces (solo para ciertos formularios)
    document.querySelectorAll('form[data-prevent-double-submit]').forEach(form => {
        let isSubmitting = false;
        form.addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                isSubmitting = true;
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                // Reactivar después de 5 segundos por seguridad (en caso de error del servidor)
                setTimeout(() => {
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);
            }
        });
    });

    // Feedback visual al hacer clic en botones
    document.querySelectorAll('.btn').forEach(btn => {
        if (!btn.hasAttribute('data-original-text')) {
            btn.setAttribute('data-original-text', btn.innerHTML);
        }
    });

    console.log('Sistema Mujeres Virtuosas S.A inicializado correctamente');
});

// ============================================
// EXPORTAR PARA USO GLOBAL
// ============================================

window.App = App;
