<!-- filepath: d:\project\client\monitoring_tekanan_air\resources\views\auth\login.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monitoring Tekanan Air</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>

</head>

<body class="bg-white">
    <div class="min-h-screen flex">
        <!-- Bagian Kiri - Gambar dan Logo -->
        <div class="hidden md:flex md:w-1/2 bg-cover bg-center relative"
            style="background-image: url('/images/pdam.png')">
            <div class="absolute inset-0" style="background-color: #090029; opacity: 0.9;"></div>
            <div class="z-10 flex flex-col items-center justify-center w-full p-10 text-white">
                <img src="/images/logo_pdam.png" alt="PDAM Logo" class="w-48 h-48 mb-8">
                <h2 class="text-5xl font-bold mb-6 text-center">Selamat Datang</h2>
                <p class="text-2xl text-center">Perumda Tirta Sukapura</p>
            </div>
        </div>

        <!-- Bagian Kanan - Form Login (tanpa bg-white dan full width) -->
        <div class="w-full p-16 md:w-1/2 flex items-center justify-center">
            <div class="p-8 w-full mx-4">
                <h1
                    class="text-5xl align-center uppercase font-semibold leading-[44px] text-center text-[#1D1D1D] mb-6">
                    Monitoring <br /> Tekanan Air</h1>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="block text-[#515151] mb-2">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            autofocus
                            class="w-full px-3 py-4 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200"
                            placeholder="Masukan Email">
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-[#515151] mb-2">Password</label>
                        <input type="password" name="password" id="password" required
                            class="w-full px-3 py-4 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200"
                            placeholder="Masukan Password">
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="remember" id="remember"
                                class="rounded border-gray-300 text-blue-500 focus:ring-blue-200">
                            <label for="remember" class="ml-2 text-gray-700">Ingat Saya</label>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full text-white py-4 px-4 rounded-md focus:outline-none focus:ring focus:ring-indigo-200 transition duration-300 ease-in-out"
                        style="background: linear-gradient(to right, #1F008F, #090029); transform: scale(1);"
                        onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
