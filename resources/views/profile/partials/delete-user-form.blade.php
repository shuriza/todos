<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Hapus Akun</h2>
        <p class="mt-1 text-sm text-gray-600">
            Setelah akun dihapus, semua data akan dihapus secara permanen. Pastikan kamu sudah mengunduh data yang diperlukan sebelum menghapus akun.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">Yakin ingin menghapus akun?</h2>

            <p class="mt-1 text-sm text-gray-600">
                Setelah akun dihapus, semua data akan dihapus secara permanen.
                @if(empty(Auth::user()->google_id))
                    Masukkan password untuk mengonfirmasi.
                @else
                    Ketik alamat email kamu (<strong>{{ Auth::user()->email }}</strong>) untuk mengonfirmasi.
                @endif
            </p>

            <div class="mt-6">
                @if(empty(Auth::user()->google_id))
                    {{-- User dengan password --}}
                    <x-input-label for="password" value="Password" class="sr-only" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Password"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                @else
                    {{-- Google-only user: konfirmasi dengan email --}}
                    <x-input-label for="confirm_email" value="Email" class="sr-only" />
                    <x-text-input
                        id="confirm_email"
                        name="confirm_email"
                        type="email"
                        class="mt-1 block w-3/4"
                        placeholder="{{ Auth::user()->email }}"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('confirm_email')" class="mt-2" />
                @endif
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
