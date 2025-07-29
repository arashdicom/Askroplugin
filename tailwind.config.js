/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.php',
        './includes/**/*.php',
        './assets/js/src/**/*.js',
        './assets/css/src/**/*.css'
    ],
    theme: {
        extend: {
            colors: {
                'askme': {
                    primary: '#6366f1',
                    secondary: '#8b5cf6',
                    success: '#10b981',
                    warning: '#f59e0b',
                    error: '#ef4444',
                    info: '#3b82f6',
                    light: '#f8fafc',
                    dark: '#1e293b',
                    gray: '#64748b',
                    border: '#e2e8f0'
                }
            },
            fontFamily: {
                'arabic': ['Cairo', 'Tajawal', 'Noto Sans Arabic', 'sans-serif'],
                'english': ['Inter', 'Roboto', 'system-ui', 'sans-serif']
            },
            fontSize: {
                'xs': ['0.75rem', { lineHeight: '1rem' }],
                'sm': ['0.875rem', { lineHeight: '1.25rem' }],
                'base': ['1rem', { lineHeight: '1.5rem' }],
                'lg': ['1.125rem', { lineHeight: '1.75rem' }],
                'xl': ['1.25rem', { lineHeight: '1.75rem' }],
                '2xl': ['1.5rem', { lineHeight: '2rem' }],
                '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
                '5xl': ['3rem', { lineHeight: '1' }],
                '6xl': ['3.75rem', { lineHeight: '1' }]
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem'
            },
            borderRadius: {
                'xl': '0.75rem',
                '2xl': '1rem',
                '3xl': '1.5rem'
            },
            boxShadow: {
                'askme': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                'askme-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                'askme-xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'bounce-in': 'bounceIn 0.6s ease-out'
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' }
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' }
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' }
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' }
                },
                bounceIn: {
                    '0%': { transform: 'scale(0.3)', opacity: '0' },
                    '50%': { transform: 'scale(1.05)' },
                    '70%': { transform: 'scale(0.9)' },
                    '100%': { transform: 'scale(1)', opacity: '1' }
                }
            },
            transitionDuration: {
                '2000': '2000ms'
            },
            zIndex: {
                '60': '60',
                '70': '70',
                '80': '80',
                '90': '90',
                '100': '100'
            }
        }
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        // Custom plugin for RTL support
        function({ addUtilities, theme }) {
            const rtlUtilities = {
                '.rtl': {
                    direction: 'rtl',
                    textAlign: 'right'
                },
                '.ltr': {
                    direction: 'ltr',
                    textAlign: 'left'
                },
                '.rtl-text-left': {
                    direction: 'rtl',
                    textAlign: 'left'
                },
                '.rtl-text-right': {
                    direction: 'rtl',
                    textAlign: 'right'
                }
            };
            addUtilities(rtlUtilities);
        },
        // Custom plugin for line clamping
        function({ addUtilities, theme }) {
            const lineClampUtilities = {
                '.line-clamp-1': {
                    overflow: 'hidden',
                    display: '-webkit-box',
                    '-webkit-box-orient': 'vertical',
                    '-webkit-line-clamp': '1'
                },
                '.line-clamp-2': {
                    overflow: 'hidden',
                    display: '-webkit-box',
                    '-webkit-box-orient': 'vertical',
                    '-webkit-line-clamp': '2'
                },
                '.line-clamp-3': {
                    overflow: 'hidden',
                    display: '-webkit-box',
                    '-webkit-box-orient': 'vertical',
                    '-webkit-line-clamp': '3'
                }
            };
            addUtilities(lineClampUtilities);
        },
        // Custom plugin for scrollbar styling
        function({ addUtilities, theme }) {
            const scrollbarUtilities = {
                '.scrollbar-thin': {
                    scrollbarWidth: 'thin',
                    scrollbarColor: `${theme('colors.gray.300')} ${theme('colors.gray.100')}`
                },
                '.scrollbar-thin::-webkit-scrollbar': {
                    width: '6px'
                },
                '.scrollbar-thin::-webkit-scrollbar-track': {
                    background: theme('colors.gray.100')
                },
                '.scrollbar-thin::-webkit-scrollbar-thumb': {
                    background: theme('colors.gray.300'),
                    borderRadius: '3px'
                },
                '.scrollbar-thin::-webkit-scrollbar-thumb:hover': {
                    background: theme('colors.gray.400')
                }
            };
            addUtilities(scrollbarUtilities);
        }
    ],
    darkMode: 'media', // or 'class' for manual dark mode toggle
    corePlugins: {
        preflight: true
    }
};

