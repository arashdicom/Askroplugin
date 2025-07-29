module.exports = {
    env: {
        browser: true,
        es2021: true,
        jquery: true,
        node: true
    },
    extends: [
        'eslint:recommended',
        'prettier'
    ],
    parserOptions: {
        ecmaVersion: 12,
        sourceType: 'module'
    },
    globals: {
        askro_ajax: 'readonly',
        wp: 'readonly',
        jQuery: 'readonly',
        $: 'readonly'
    },
    rules: {
        'no-console': 'warn',
        'no-debugger': 'warn',
        'no-unused-vars': 'warn',
        'prefer-const': 'error',
        'no-var': 'error',
        'object-shorthand': 'error',
        'prefer-template': 'error'
    }
}; 
