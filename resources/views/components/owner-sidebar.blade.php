@props(['active' => ''])

@php
    $sidebarVerifStatus = \App\Models\IdentityVerification::where('user_id', auth()->id())
        ->latest()
        ->value('status');
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     OWNER SIDEBAR — Design inspiré Airbnb
     Clean, minimaliste, blanc, typographie nette, icônes fines
     Desktop: sidebar fixe à gauche
     Mobile: bottom sheet slide-up avec bouton flottant
     ═══════════════════════════════════════════════════════════════ --}}

{{-- ===== DESKTOP SIDEBAR ===== --}}
<aside class="hidden lg:block w-70 shrink-0">
    <div class="sticky top-20">
        <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- ── Profil propriétaire ── --}}
            <div class="p-5 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div
                            class="w-12 h-12 rounded-full overflow-hidden bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center shrink-0 ring-2 ring-white shadow-sm">
                            @if (auth()->user()->profile_photo || auth()->user()->avatar)
                                <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}"
                                    alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                            @else
                                <span
                                    class="text-lg font-semibold text-gray-600">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        {{-- Indicateur en ligne --}}
                        <span
                            class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-[15px] text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <svg class="w-3 h-3 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            Propriétaire
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Navigation ── --}}
            <div class="py-2 px-2">

                {{-- Section: Principal --}}
                <div class="mb-1">
                    <p class="px-3 pt-3 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        Principal</p>
                </div>

                {{-- Dashboard --}}
                <a href="{{ route('owner.dashboard') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'dashboard' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'dashboard' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </span>
                    Dashboard
                </a>

                {{-- Annonces --}}
                <a href="{{ route('owner.residences.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'residences' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'residences' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5M10.5 21H3m16.5 0L21 12m-18 9l1.5-9m0 0V3.545M4.5 12h15M4.5 3.545h15M4.5 3.545L3 12m18-8.455L22.5 12" />
                        </svg>
                    </span>
                    Annonces
                    @php $residenceCount = auth()->user()->residences()->count() @endphp
                    @if ($residenceCount > 0)
                        <span
                            class="ml-auto text-xs {{ $active === 'residences' ? 'text-white/70' : 'text-gray-400' }}">{{ $residenceCount }}</span>
                    @endif
                </a>

                {{-- Réservations --}}
                <a href="{{ route('owner.bookings.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'bookings' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'bookings' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </span>
                    Réservations
                </a>

                {{-- Demandes de contact --}}
                <a href="{{ route('owner.contacts.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'contacts' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'contacts' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    Demandes
                    @php $pendingContacts = auth()->user()->pendingContactsCount() @endphp
                    @if ($pendingContacts > 0)
                        <span
                            class="ml-auto flex items-center justify-center min-w-5 h-5 px-1.5 text-[11px] font-bold rounded-full
                                     {{ $active === 'contacts' ? 'bg-white text-gray-900' : 'bg-rose-500 text-white' }}">
                            {{ $pendingContacts }}
                        </span>
                    @endif
                </a>

                {{-- Messages --}}
                <a href="{{ route('chat.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'messages' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'messages' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                    </span>
                    Messages
                    @php $unreadMessages = auth()->user()->unreadMessagesCount() @endphp
                    @if ($unreadMessages > 0)
                        <span
                            class="ml-auto flex items-center justify-center min-w-5 h-5 px-1.5 text-[11px] font-bold rounded-full
                                     {{ $active === 'messages' ? 'bg-white text-gray-900' : 'bg-rose-500 text-white' }}">
                            {{ $unreadMessages }}
                        </span>
                    @endif
                </a>

                {{-- Section: Marketing --}}
                <div class="mt-5 mb-1">
                    <p class="px-3 pt-1 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        Marketing</p>
                </div>

                {{-- Promotions --}}
                <a href="{{ route('owner.marketing.promotions.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'promotions' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'promotions' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </span>
                    Promotions
                </a>

                {{-- Codes promo --}}
                <a href="{{ route('owner.marketing.coupons.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'coupons' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'coupons' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                        </svg>
                    </span>
                    Codes promo
                </a>

                {{-- Mise en avant --}}
                <a href="{{ route('owner.marketing.sponsored.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'sponsored' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'sponsored' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </span>
                    Mise en avant
                </a>

                {{-- Parrainages --}}
                <a href="{{ route('owner.marketing.referrals.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'referrals' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'referrals' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </span>
                    Parrainages
                </a>

                {{-- Section: Outils --}}
                <div class="mt-5 mb-1">
                    <p class="px-3 pt-1 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Outils
                    </p>
                </div>

                {{-- Analytics --}}
                <a href="{{ route('owner.analytics.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'analytics' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'analytics' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </span>
                    Analytics
                </a>

                {{-- Comparer --}}
                <a href="{{ route('owner.compare.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'compare' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'compare' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </span>
                    Comparer
                </a>

                {{-- Revenus --}}
                <a href="{{ route('owner.earnings.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'earnings' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'earnings' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </span>
                    Revenus
                </a>

                {{-- Contrats de bail --}}
                <a href="{{ route('owner.lease-contracts.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'lease-contracts' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'lease-contracts' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </span>
                    Contrats
                </a>

                {{-- Réponses auto --}}
                <a href="{{ route('owner.auto-replies.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'auto-replies' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'auto-replies' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </span>
                    Réponses auto
                </a>

                {{-- Section: Gestion --}}
                <div class="mt-5 mb-1">
                    <p class="px-3 pt-1 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Gestion</p>
                </div>

                {{-- Dépenses --}}
                <a href="{{ route('owner.expenses.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'expenses' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'expenses' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                    </span>
                    Dépenses
                </a>

                {{-- Relances loyer --}}
                <a href="{{ route('owner.rent-reminders.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'rent-reminders' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'rent-reminders' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                    </span>
                    Relances loyer
                </a>

                {{-- Maintenance --}}
                <a href="{{ route('owner.maintenance.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'maintenance' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'maintenance' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" /></svg>
                    </span>
                    Maintenance
                </a>

                {{-- Documents --}}
                <a href="{{ route('owner.documents.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'documents' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'documents' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </span>
                    Documents
                </a>

                {{-- Ménage --}}
                <a href="{{ route('owner.cleaning.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'cleaning' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'cleaning' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    </span>
                    Ménage
                </a>

                {{-- Section: Avancé --}}
                <div class="mt-5 mb-1">
                    <p class="px-3 pt-1 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Avancé</p>
                </div>

                {{-- Avis locataires --}}
                <a href="{{ route('owner.tenant-reviews.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'tenant-reviews' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'tenant-reviews' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                    </span>
                    Avis locataires
                </a>

                {{-- Rapport fiscal --}}
                <a href="{{ route('owner.fiscal-reports.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'fiscal-reports' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'fiscal-reports' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </span>
                    Rapport fiscal
                </a>

                {{-- Calendrier --}}
                <a href="{{ route('owner.calendar.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'calendar' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'calendar' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                    </span>
                    Calendrier
                </a>

                {{-- Portfolio --}}
                <a href="{{ route('owner.portfolio.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'portfolio' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'portfolio' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
                    </span>
                    Portfolio
                </a>

                {{-- Séparateur Automatisation --}}
                <div class="mt-4 mb-2 px-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Automatisation</span>
                </div>

                {{-- Séquences messages --}}
                <a href="{{ route('owner.sequences.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'sequences' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'sequences' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                    </span>
                    Séquences messages
                </a>

                {{-- Synchronisation iCal --}}
                <a href="{{ route('owner.ical.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'ical' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'ical' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    </span>
                    Sync iCal
                </a>

                {{-- Serrures connectées --}}
                <a href="{{ route('owner.smart-locks.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'smart-locks' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'smart-locks' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                    </span>
                    Serrures
                </a>

                {{-- Compteurs --}}
                <a href="{{ route('owner.utilities.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'utilities' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'utilities' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>
                    </span>
                    Compteurs
                </a>

                {{-- Séparateur Performance --}}
                <div class="mt-4 mb-2 px-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Performance</span>
                </div>

                {{-- KPIs --}}
                <a href="{{ route('owner.performance.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'performance' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'performance' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                    </span>
                    KPIs
                </a>

                {{-- Yield Management --}}
                <a href="{{ route('owner.yield.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'yield' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'yield' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                    </span>
                    Yield
                </a>

                {{-- Guest Screening --}}
                <a href="{{ route('owner.screening.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'screening' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'screening' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    </span>
                    Scoring
                </a>

                {{-- Alertes --}}
                <a href="{{ route('owner.alerts.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'alerts' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'alerts' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                    </span>
                    Alertes
                </a>

                {{-- Séparateur Voyageurs --}}
                <div class="mt-4 mb-2 px-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Voyageurs</span>
                </div>

                {{-- Guidebooks --}}
                <a href="{{ route('owner.guidebooks.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'guidebooks' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'guidebooks' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    Guides
                </a>

                {{-- Dommages --}}
                <a href="{{ route('owner.damages.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'damages' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'damages' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </span>
                    Dommages
                </a>

                {{-- Réclamations assurance --}}
                <a href="{{ route('owner.insurance-claims.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'insurance-claims' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'insurance-claims' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </span>
                    Réclamations
                </a>

                {{-- Séparateur Réglages --}}
                <div class="mt-4 mb-2 px-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Réglages</span>
                </div>

                {{-- Mode vacances --}}
                <a href="{{ route('owner.vacation-mode.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'vacation-mode' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'vacation-mode' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                    </span>
                    Mode vacances
                </a>

                {{-- Assurance --}}
                <a href="{{ route('owner.insurance.index') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'insurance' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $active === 'insurance' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    </span>
                    Assurance
                </a>

                {{-- Vérification --}}
                <a href="{{ route('verification.dashboard') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'verification' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'verification' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </span>
                    Vérification
                    @if (!$sidebarVerifStatus || in_array($sidebarVerifStatus, ['rejected', 'expired']))
                        <span class="ml-auto w-2 h-2 rounded-full bg-amber-400"></span>
                    @elseif($sidebarVerifStatus === 'approved')
                        <span class="ml-auto">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </a>

                {{-- Séparateur --}}
                <div class="my-3 mx-3 border-t border-gray-100"></div>

                {{-- Profil --}}
                <a href="{{ route('profile.edit') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'profile' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'profile' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    Paramètres
                </a>
            </div>

            {{-- ── CTA: Nouvelle annonce ── --}}
            <div class="p-4 border-t border-gray-100">
                <a href="{{ route('owner.residences.create') }}"
                    class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-linear-to-r from-rose-500 to-pink-600 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md hover:from-rose-600 hover:to-pink-700 transition-all duration-200 active:scale-[0.98]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nouvelle annonce
                </a>
            </div>
        </nav>
    </div>
</aside>

{{-- ===== MOBILE BOTTOM TAB BAR (Airbnb-style) ===== --}}
@php
    $unreadMsgCount = auth()->user()->unreadMessagesCount();
    $pendingBookingsCount = \App\Models\Booking::whereHas('residence', fn($q) => $q->where('owner_id', auth()->id()))
        ->where('status', 'pending')
        ->count();
@endphp
<div x-data="{ moreOpen: false }" class="lg:hidden">

    {{-- Fixed bottom tab bar --}}
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200"
        style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="grid grid-cols-5 h-14 text-[10px] xs:text-xs">
            <a href="{{ route('owner.dashboard') }}"
                class="flex flex-col items-center justify-center gap-0.5 {{ $active === 'dashboard' ? 'text-gray-900' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ $active === 'dashboard' ? 'currentColor' : 'none' }}"
                    stroke="currentColor" stroke-width="{{ $active === 'dashboard' ? '0' : '1.8' }}"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="text-[10px] font-semibold">Accueil</span>
            </a>
            <a href="{{ route('owner.residences.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 {{ $active === 'residences' ? 'text-gray-900' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5M10.5 21H3m16.5 0L21 12m-18 9l1.5-9" />
                </svg>
                <span class="text-[10px] font-semibold">Annonces</span>
            </a>
            <a href="{{ route('owner.bookings.index') }}"
                class="relative flex flex-col items-center justify-center gap-0.5 {{ $active === 'bookings' ? 'text-gray-900' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    @if ($pendingBookingsCount > 0)
                        <span
                            class="absolute -top-1.5 -right-2 min-w-4 h-4 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-1">{{ min($pendingBookingsCount, 9) }}</span>
                    @endif
                </div>
                <span class="text-[10px] font-semibold">Réservations</span>
            </a>
            <a href="{{ route('chat.index') }}"
                class="relative flex flex-col items-center justify-center gap-0.5 {{ $active === 'messages' ? 'text-gray-900' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    @if ($unreadMsgCount > 0)
                        <span
                            class="absolute -top-1.5 -right-2 min-w-4 h-4 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-1">{{ min($unreadMsgCount, 9) }}</span>
                    @endif
                </div>
                <span class="text-[10px] font-semibold">Messages</span>
            </a>
            <button @click="moreOpen = !moreOpen"
                class="flex flex-col items-center justify-center gap-0.5 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span class="text-[10px] font-semibold">Plus</span>
            </button>
        </div>
    </nav>
    <div class="h-14"></div>

    {{-- Overlay --}}
    <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="moreOpen = false"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40" x-cloak></div>

    {{-- "Plus" slide-up --}}
    <div x-show="moreOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[75vh] overflow-y-auto overscroll-contain"
        x-cloak>
        <div class="flex justify-center pt-3 pb-1 sticky top-0 bg-white rounded-t-3xl z-10">
            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
        </div>
        <div class="px-5 py-3 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full overflow-hidden bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center shrink-0">
                    @if (auth()->user()->profile_photo || auth()->user()->avatar)
                        <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}"
                            alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        <span
                            class="text-base font-semibold text-gray-600">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-gray-900 text-[15px]">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Espace propriétaire</p>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 space-y-0.5">
            <p class="px-2 pt-1 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Gestion</p>
            @foreach ([
        ['route' => 'owner.contacts.index', 'key' => 'contacts', 'label' => 'Demandes', 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['route' => 'owner.earnings.index', 'key' => 'earnings', 'label' => 'Revenus', 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ['route' => 'owner.analytics.index', 'key' => 'analytics', 'label' => 'Analytics', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ] as $item)
                <a href="{{ route($item['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $item['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <svg class="w-5 h-5 {{ $active === $item['key'] ? 'text-white' : 'text-gray-400' }}"
                        fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Marketing</p>
            @foreach ([['route' => 'owner.marketing.promotions.index', 'key' => 'promotions', 'label' => 'Promotions'], ['route' => 'owner.marketing.coupons.index', 'key' => 'coupons', 'label' => 'Codes promo'], ['route' => 'owner.marketing.sponsored.index', 'key' => 'sponsored', 'label' => 'Mise en avant'], ['route' => 'owner.marketing.referrals.index', 'key' => 'referrals', 'label' => 'Parrainages']] as $mItem)
                <a href="{{ route($mItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $mItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span
                        class="w-5 h-5 flex items-center justify-center {{ $active === $mItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $mItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Outils</p>
            @foreach ([['route' => 'owner.compare.index', 'key' => 'compare', 'label' => 'Comparer'], ['route' => 'owner.auto-replies.index', 'key' => 'auto-replies', 'label' => 'Réponses auto'], ['route' => 'verification.dashboard', 'key' => 'verification', 'label' => 'Vérification']] as $tItem)
                <a href="{{ route($tItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $tItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span
                        class="w-5 h-5 flex items-center justify-center {{ $active === $tItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $tItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Gestion</p>
            @foreach ([
                ['route' => 'owner.lease-contracts.index', 'key' => 'lease-contracts', 'label' => 'Contrats'],
                ['route' => 'owner.expenses.index', 'key' => 'expenses', 'label' => 'Dépenses'],
                ['route' => 'owner.rent-reminders.index', 'key' => 'rent-reminders', 'label' => 'Relances loyer'],
                ['route' => 'owner.maintenance.index', 'key' => 'maintenance', 'label' => 'Maintenance'],
                ['route' => 'owner.documents.index', 'key' => 'documents', 'label' => 'Documents'],
                ['route' => 'owner.cleaning.index', 'key' => 'cleaning', 'label' => 'Ménage'],
            ] as $gItem)
                <a href="{{ route($gItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $gItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span class="w-5 h-5 flex items-center justify-center {{ $active === $gItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $gItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Avancé</p>
            @foreach ([
                ['route' => 'owner.tenant-reviews.index', 'key' => 'tenant-reviews', 'label' => 'Avis locataires'],
                ['route' => 'owner.fiscal-reports.index', 'key' => 'fiscal-reports', 'label' => 'Rapport fiscal'],
                ['route' => 'owner.calendar.index', 'key' => 'calendar', 'label' => 'Calendrier'],
                ['route' => 'owner.portfolio.index', 'key' => 'portfolio', 'label' => 'Portfolio'],
                ['route' => 'owner.vacation-mode.index', 'key' => 'vacation-mode', 'label' => 'Mode vacances'],
                ['route' => 'owner.insurance.index', 'key' => 'insurance', 'label' => 'Assurance'],
            ] as $aItem)
                <a href="{{ route($aItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $aItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span class="w-5 h-5 flex items-center justify-center {{ $active === $aItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $aItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Automatisation</p>
            @foreach ([
                ['route' => 'owner.sequences.index', 'key' => 'sequences', 'label' => 'Séquences messages'],
                ['route' => 'owner.ical.index', 'key' => 'ical', 'label' => 'Sync iCal'],
                ['route' => 'owner.smart-locks.index', 'key' => 'smart-locks', 'label' => 'Serrures'],
                ['route' => 'owner.utilities.index', 'key' => 'utilities', 'label' => 'Compteurs'],
            ] as $autoItem)
                <a href="{{ route($autoItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $autoItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span class="w-5 h-5 flex items-center justify-center {{ $active === $autoItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $autoItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Performance</p>
            @foreach ([
                ['route' => 'owner.performance.index', 'key' => 'performance', 'label' => 'KPIs'],
                ['route' => 'owner.yield.index', 'key' => 'yield', 'label' => 'Yield'],
                ['route' => 'owner.screening.index', 'key' => 'screening', 'label' => 'Scoring'],
                ['route' => 'owner.alerts.index', 'key' => 'alerts', 'label' => 'Alertes'],
            ] as $perfItem)
                <a href="{{ route($perfItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $perfItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span class="w-5 h-5 flex items-center justify-center {{ $active === $perfItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $perfItem['label'] }}
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Voyageurs</p>
            @foreach ([
                ['route' => 'owner.guidebooks.index', 'key' => 'guidebooks', 'label' => 'Guides'],
                ['route' => 'owner.damages.index', 'key' => 'damages', 'label' => 'Dommages'],
                ['route' => 'owner.insurance-claims.index', 'key' => 'insurance-claims', 'label' => 'Réclamations'],
                ['route' => 'owner.onboarding.index', 'key' => 'onboarding', 'label' => 'Démarrage'],
            ] as $guestItem)
                <a href="{{ route($guestItem['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $guestItem['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <span class="w-5 h-5 flex items-center justify-center {{ $active === $guestItem['key'] ? 'text-white' : 'text-gray-400' }}">●</span>
                    {{ $guestItem['label'] }}
                </a>
            @endforeach

            <div class="my-2 border-t border-gray-100"></div>
            <a href="{{ route('profile.edit') }}" @click="moreOpen = false"
                class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium text-gray-700 active:bg-gray-50">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Paramètres
            </a>
            <div class="pt-3 pb-2">
                <a href="{{ route('owner.residences.create') }}"
                    class="flex items-center justify-center gap-2 w-full px-4 py-3.5 bg-linear-to-r from-rose-500 to-pink-600 text-white text-[15px] font-semibold rounded-2xl shadow-sm active:scale-[0.98] transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nouvelle annonce
                </a>
            </div>
        </div>
        <div class="h-6"></div>
    </div>
</div>

