<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | Kesalahan Internal Server - Drastha Learning</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS (Direct from CDN for standalone page styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#1A2B49',
                            blue: '#264790',
                            accent: '#44A6D9',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.05); }
        }
        .pulse-animation {
            animation: pulse-slow 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-tr from-[#0F172A] via-[#1E293B] to-[#0F172A] text-slate-200 min-h-screen flex items-center justify-center p-6 relative overflow-hidden font-sans">
    <!-- Subtle Background Glows -->
    <div class="absolute w-96 h-96 rounded-full bg-red-500/5 blur-3xl -top-20 -left-20 pulse-animation"></div>
    <div class="absolute w-96 h-96 rounded-full bg-brand-blue/10 blur-3xl -bottom-20 -right-20 pulse-animation"></div>

    <div class="max-w-md w-full text-center relative z-10">
        <!-- Error Code -->
        <div class="relative mb-8 inline-block">
            <h1 class="text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-400 via-white to-brand-blue select-none">
                500
            </h1>
            <div class="absolute -inset-1 rounded-lg bg-gradient-to-r from-red-500 to-brand-blue opacity-30 blur-xl -z-10"></div>
        </div>

        <!-- Error Message -->
        <h2 class="text-2xl md:text-3xl font-extrabold text-white mb-3">
            Kesalahan Internal Server
        </h2>
        <p class="text-slate-400 mb-8 leading-relaxed text-sm md:text-base">
            Maaf, terjadi masalah internal pada server kami yang menghalangi penyelesaian request Anda. Tim teknis kami telah dinotifikasi secara otomatis. Silakan coba sesaat lagi.
        </p>

        <!-- Navigation Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a 
                href="/" 
                class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-brand-blue to-brand-navy hover:from-[#1d3870] hover:to-[#111e33] text-white font-bold rounded-2xl shadow-lg shadow-brand-blue/20 transition-all duration-300 transform hover:-translate-y-0.5"
            >
                Kembali Ke Beranda
            </a>
            <a 
                href="javascript:location.reload()" 
                class="w-full sm:w-auto px-8 py-3.5 bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 hover:text-white font-semibold rounded-2xl border border-slate-700 transition-all duration-300 transform hover:-translate-y-0.5"
            >
                Muat Ulang Halaman
            </a>
        </div>
    </div>
</body>
</html>
