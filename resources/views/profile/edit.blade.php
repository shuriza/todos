<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Profil & Pengaturan</h2>
            <p class="text-sm text-gray-500">Kelola informasi akun, integrasi, dan preferensi notifikasi</p>
        </div>
    </x-slot>

    <div class="p-4 lg:p-6">
        <div class="max-w-3xl mx-auto space-y-6">

            {{-- Profile Card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="h-24 bg-gradient-to-br from-indigo-500 to-purple-600 relative">
                    <div class="absolute -bottom-10 left-6">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" class="w-20 h-20 rounded-xl border-4 border-white shadow-lg object-cover" alt="Avatar">
                        @else
                            <div class="w-20 h-20 rounded-xl border-4 border-white shadow-lg bg-indigo-100 flex items-center justify-center">
                                <span class="text-2xl font-bold text-indigo-600">{{ $user->initials ?? substr($user->name, 0, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="pt-14 px-6 pb-6">
                    <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        @if($user->nim)
                            <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-lg font-medium">NIM: {{ $user->nim }}</span>
                        @endif
                        @if($user->prodi)
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-lg font-medium">{{ $user->prodi }}</span>
                        @endif
                        @if($user->google_id)
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-lg font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" viewBox="0 0 24 24"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/></svg>
                                Google
                            </span>
                        @endif
                        @if($user->hasTelegram())
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-lg font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                Telegram
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Update Profile Information --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Informasi Profil</h3>
                <p class="text-sm text-gray-500 mb-5">Perbarui informasi dan data akademik kamu</p>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('patch')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <x-input-error class="mt-1" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <x-input-error class="mt-1" :messages="$errors->get('email')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nim" class="block text-sm font-medium text-gray-700 mb-1.5">NIM</label>
                            <input type="text" id="nim" name="nim" value="{{ old('nim', $user->nim) }}" placeholder="Contoh: 2341234567"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <x-input-error class="mt-1" :messages="$errors->get('nim')" />
                        </div>
                        <div>
                            <label for="prodi" class="block text-sm font-medium text-gray-700 mb-1.5">Program Studi</label>
                            <input type="text" id="prodi" name="prodi" value="{{ old('prodi', $user->prodi) }}" placeholder="Contoh: D-IV Teknik Informatika"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <x-input-error class="mt-1" :messages="$errors->get('prodi')" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        @if (session('status') === 'profile-updated')
                            <p class="text-sm text-green-600 font-medium" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">Berhasil disimpan!</p>
                        @endif
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Integrations --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Integrasi</h3>
                <p class="text-sm text-gray-500 mb-5">Hubungkan akun dengan layanan eksternal</p>

                <div class="space-y-4">
                    {{-- Google Classroom --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-xl border border-gray-200 flex items-center justify-center">
                                <svg class="w-6 h-6" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Google Classroom</p>
                                @if($user->hasGoogleClassroom())
                                    <p class="text-xs text-green-600 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Terhubung
                                    </p>
                                @else
                                    <p class="text-xs text-gray-500">Belum terhubung</p>
                                @endif
                            </div>
                        </div>
                        @if($user->hasGoogleClassroom())
                            <a href="{{ route('classroom.index') }}" class="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                                Kelola
                            </a>
                        @else
                            <a href="{{ route('auth.google') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                Hubungkan
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Telegram Integration (Full Setup) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" x-data="telegramSettings()">

                {{-- Header --}}
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Telegram Notifikasi</h3>
                                <p class="text-sm text-gray-500">Terima pengingat tugas langsung di Telegram</p>
                            </div>
                        </div>
                        <div>
                            @if($user->hasTelegram())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    Terhubung
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-gray-500 bg-gray-100 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                    Belum Terhubung
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Setup Guide (show if not connected) --}}
                    @unless($user->hasTelegram())
                    <div class="bg-blue-50 rounded-xl p-5 border border-blue-100">
                        <h4 class="font-semibold text-blue-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Cara Menghubungkan Telegram
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mt-0.5">1</span>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Buka Telegram & cari bot</p>
                                    <p class="text-xs text-blue-700 mt-0.5">Cari <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">@userinfobot</code> di Telegram</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mt-0.5">2</span>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Dapatkan Chat ID</p>
                                    <p class="text-xs text-blue-700 mt-0.5">Kirim pesan <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">/start</code> ke @userinfobot untuk mendapatkan Chat ID kamu</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mt-0.5">3</span>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Mulai chat dengan bot notifikasi</p>
                                    <p class="text-xs text-blue-700 mt-0.5">Cari bot <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">{{ config('services.telegram.bot_username') ?: '@YourBotUsername' }}</code> dan kirim <code class="bg-blue-100 px-1.5 py-0.5 rounded font-mono">/start</code></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center mt-0.5">4</span>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Masukkan Chat ID di bawah</p>
                                    <p class="text-xs text-blue-700 mt-0.5">Paste Chat ID (angka) yang kamu dapatkan di kolom di bawah ini</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endunless

                    {{-- Chat ID Input --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telegram Chat ID</label>
                        <div class="flex items-center gap-3">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                </div>
                                <input type="text" x-model="chatId" placeholder="Contoh: 123456789"
                                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                       :class="{ 'border-green-400 bg-green-50': connected && chatId, 'border-gray-300': !connected }">
                            </div>
                            <button @click="saveChatId()" :disabled="saving || !chatId"
                                    class="px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 whitespace-nowrap">
                                <template x-if="saving">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </template>
                                <template x-if="!saving">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </template>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                            </button>
                        </div>
                        <template x-if="saveMessage">
                            <p class="mt-2 text-sm" :class="saveSuccess ? 'text-green-600' : 'text-red-600'" x-text="saveMessage" x-transition></p>
                        </template>
                    </div>

                    {{-- Test Notification Button --}}
                    @if($user->hasTelegram())
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Tes Notifikasi</p>
                            <p class="text-xs text-gray-500">Kirim pesan percobaan ke Telegram kamu</p>
                        </div>
                        <button @click="testNotification()" :disabled="testing"
                                class="px-4 py-2 text-sm font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors disabled:opacity-50 flex items-center gap-2">
                            <template x-if="testing">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <template x-if="!testing">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            <span x-text="testing ? 'Mengirim...' : 'Kirim Tes'"></span>
                        </button>
                    </div>
                    <template x-if="testMessage">
                        <p class="text-sm" :class="testSuccess ? 'text-green-600' : 'text-red-600'" x-text="testMessage" x-transition></p>
                    </template>
                    @endif

                    {{-- Notification Preferences (show if connected) --}}
                    @if($user->hasTelegram())
                    <div class="border-t border-gray-100 pt-6">
                        <h4 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                            Preferensi Notifikasi
                            <span x-show="prefMessage" x-transition.duration.300ms
                                  class="text-xs font-medium px-2 py-0.5 rounded-full"
                                  :class="prefSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  x-text="prefMessage"></span>
                        </h4>
                        <p class="text-sm text-gray-500 mb-4">Pilih jenis notifikasi yang ingin kamu terima</p>

                        <div class="space-y-4">
                            {{-- Deadline Reminder --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Pengingat Deadline</p>
                                        <p class="text-xs text-gray-500">Notifikasi sebelum deadline tugas</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="prefs.deadline_reminder" @change="autoSavePrefs()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            {{-- Reminder Hours (show if deadline reminder enabled) --}}
                            <div x-show="prefs.deadline_reminder" x-transition class="pl-4">
                                <div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg border border-orange-100">
                                    <label class="text-sm text-orange-800 font-medium whitespace-nowrap">Ingatkan</label>
                                    <select x-model.number="prefs.reminder_hours" @change="autoSavePrefs()"
                                            class="rounded-lg border-orange-200 bg-white text-sm py-1.5 text-orange-800 focus:border-orange-400 focus:ring-orange-400">
                                        <optgroup label="Menit">
                                            <option value="0.0167">1 menit</option>
                                            <option value="0.0833">5 menit</option>
                                            <option value="0.1667">10 menit</option>
                                            <option value="0.25">15 menit</option>
                                            <option value="0.5">30 menit</option>
                                        </optgroup>
                                        <optgroup label="Jam">
                                            <option value="1">1 jam</option>
                                            <option value="2">2 jam</option>
                                            <option value="3">3 jam</option>
                                            <option value="6">6 jam</option>
                                            <option value="12">12 jam</option>
                                            <option value="24">1 hari</option>
                                            <option value="48">2 hari</option>
                                        </optgroup>
                                    </select>
                                    <span class="text-sm text-orange-800">sebelum deadline</span>
                                </div>
                            </div>

                            {{-- Overdue Alert --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Peringatan Overdue</p>
                                        <p class="text-xs text-gray-500">Notifikasi saat tugas melewati deadline</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="prefs.overdue_alert" @change="autoSavePrefs()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            {{-- Daily Summary --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Rangkuman Harian</p>
                                        <p class="text-xs text-gray-500">Ringkasan tugas setiap pagi</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="prefs.daily_summary" @change="autoSavePrefs()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            {{-- Daily Summary Time (show if daily summary enabled) --}}
                            <div x-show="prefs.daily_summary" x-transition class="pl-4">
                                <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                                    <label class="text-sm text-indigo-800 font-medium whitespace-nowrap">Kirim jam</label>
                                    <input type="time" x-model="prefs.daily_summary_time" @change="autoSavePrefs()"
                                           class="rounded-lg border-indigo-200 bg-white text-sm py-1.5 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-400">
                                    <span class="text-sm text-indigo-800">setiap hari</span>
                                </div>
                            </div>

                            {{-- Classroom Sync --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Sinkronisasi Classroom</p>
                                        <p class="text-xs text-gray-500">Notifikasi saat ada tugas baru dari Classroom</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="prefs.classroom_sync" @change="autoSavePrefs()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Preferences Save Status --}}
                        <template x-if="prefMessage">
                            <p class="mt-4 text-sm" :class="prefSuccess ? 'text-green-600' : 'text-red-600'" x-text="prefMessage" x-transition></p>
                        </template>
                    </div>

                    {{-- Notification History (Quick Stats) --}}
                    <div class="border-t border-gray-100 pt-6">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-900">Riwayat Notifikasi</h4>
                            <button @click="loadHistory()" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Muat Ulang</button>
                        </div>

                        {{-- Stats Row --}}
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-green-50 rounded-lg p-3 text-center border border-green-100">
                                <p class="text-lg font-bold text-green-700" x-text="stats.sent">0</p>
                                <p class="text-xs text-green-600">Terkirim</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-3 text-center border border-red-100">
                                <p class="text-lg font-bold text-red-700" x-text="stats.failed">0</p>
                                <p class="text-xs text-red-600">Gagal</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center border border-blue-100">
                                <p class="text-lg font-bold text-blue-700" x-text="stats.today">0</p>
                                <p class="text-xs text-blue-600">Hari Ini</p>
                            </div>
                        </div>

                        {{-- Recent History --}}
                        <div class="space-y-2 max-h-64 overflow-y-auto" x-show="historyItems.length > 0" x-transition>
                            <template x-for="item in historyItems" :key="item.id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="flex-shrink-0">
                                        <template x-if="item.status_kirim === 'sent'">
                                            <span class="w-2 h-2 rounded-full bg-green-500 block"></span>
                                        </template>
                                        <template x-if="item.status_kirim === 'failed'">
                                            <span class="w-2 h-2 rounded-full bg-red-500 block"></span>
                                        </template>
                                        <template x-if="item.status_kirim === 'pending'">
                                            <span class="w-2 h-2 rounded-full bg-yellow-500 block"></span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 truncate" x-text="item.todo ? item.todo.title : 'Rangkuman Harian'"></p>
                                        <p class="text-xs text-gray-500" x-text="formatDate(item.created_at)"></p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap" 
                                          :class="{
                                              'bg-green-100 text-green-700': item.status_kirim === 'sent',
                                              'bg-red-100 text-red-700': item.status_kirim === 'failed',
                                              'bg-yellow-100 text-yellow-700': item.status_kirim === 'pending'
                                          }"
                                          x-text="item.status_kirim === 'sent' ? 'Terkirim' : (item.status_kirim === 'failed' ? 'Gagal' : 'Pending')"></span>
                                </div>
                            </template>
                        </div>
                        <div x-show="historyItems.length === 0 && !loadingHistory" class="text-center py-6">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <p class="text-sm text-gray-400">Belum ada riwayat notifikasi</p>
                        </div>
                    </div>
                    @endif

                    {{-- Disconnect Button --}}
                    @if($user->hasTelegram())
                    <div class="border-t border-gray-100 pt-4">
                        <button @click="if(confirm('Putuskan koneksi Telegram? Semua preferensi notifikasi akan direset.')) disconnectTelegram()"
                                class="text-sm text-red-600 hover:text-red-700 font-medium flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Putuskan Telegram
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Change Password --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Ubah Password</h3>
                <p class="text-sm text-gray-500 mb-5">Pastikan akun menggunakan password yang kuat</p>

                @include('profile.partials.update-password-form')
            </div>

            {{-- Danger Zone --}}
            <div class="bg-white rounded-xl border border-red-200 shadow-sm p-6">
                <h3 class="text-lg font-bold text-red-600 mb-1">Zona Bahaya</h3>
                <p class="text-sm text-gray-500 mb-5">Tindakan berikut tidak dapat dibatalkan</p>

                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $telegramData = [
            'chatId' => $user->telegram_chat_id ?? '',
            'connected' => $user->hasTelegram(),
            'prefs' => [
                'deadline_reminder' => (bool) $user->getNotifPref('deadline_reminder', true),
                'daily_summary' => (bool) $user->getNotifPref('daily_summary', false),
                'overdue_alert' => (bool) $user->getNotifPref('overdue_alert', true),
                'classroom_sync' => (bool) $user->getNotifPref('classroom_sync', true),
                'reminder_hours' => $user->getNotifPref('reminder_hours', 2),
                'daily_summary_time' => $user->getNotifPref('daily_summary_time', '07:00'),
            ],
            'routes' => [
                'saveChatId' => route('notifications.telegram.save-chat-id'),
                'test' => route('notifications.telegram.test'),
                'preferences' => route('notifications.preferences.update'),
                'stats' => route('notifications.stats'),
                'history' => route('notifications.history'),
                'disconnect' => route('notifications.telegram.disconnect'),
            ],
        ];
    @endphp
    <script id="telegram-data" type="application/json">
        {!! json_encode($telegramData) !!}
    </script>
    {{-- JS loaded from resources/js/pages/telegram.js --}}
    @endpush
</x-app-layout>
