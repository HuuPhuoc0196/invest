import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 
                'resources/css/adminInsert.css',
                'resources/css/adminView.css',
                'resources/css/login.css',
                'resources/css/loginRegister.css',
                'resources/css/userFollow.css',
                'resources/css/footer.css',
                'resources/js/Admin.js',
                'resources/js/User.js',
                'resources/js/app.js'],
            refresh: true,
        }),
    ],
    
});
