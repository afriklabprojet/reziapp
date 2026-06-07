@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="supportShow()">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('support.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour au centre d'aide
        </a>
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center space-x-2 mb-2">
                    <span class="text-sm text-gray-500">{{ $ticket->ticket_number }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                        {{ $ticket->status_label }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ $ticket->category_label }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-gray-900">{{ $ticket->subject }}</h1>
            </div>
            <div class="flex items-center space-x-2">
                @if($ticket->isActive())
                    <form action="{{ route('support.close', $ticket) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                            Fermer le ticket
                        </button>
                    </form>
                @else
                    <form action="{{ route('support.reopen', $ticket) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                            Réouvrir
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Messages -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <!-- Messages List -->
                <div class="divide-y max-h-150 overflow-y-auto" id="messages-container">
                    @foreach($ticket->messages as $message)
                        @if($message->is_internal_note)
                            @continue
                        @endif
                        <div class="p-4 {{ $message->is_from_customer ? 'bg-white' : 'bg-blue-50' }}">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $message->is_from_customer ? 'bg-gray-200' : 'bg-blue-200' }}">
                                    @if($message->is_from_customer)
                                        <span class="text-gray-600 font-medium">{{ substr($message->user->name, 0, 1) }}</span>
                                    @else
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900">
                                            {{ $message->is_from_customer ? $message->user->name : 'Support Rezi Studio Meublé Faya' }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $message->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    <div class="text-gray-700 whitespace-pre-line">{{ $message->message }}</div>

                                    @if($message->has_attachments)
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($message->attachments as $index => $attachment)
                                                <a href="{{ route('support.attachments.download', [$ticket, $message, $index]) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center px-3 py-1 bg-gray-100 rounded text-sm text-gray-700 hover:bg-gray-200">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                    </svg>
                                                    {{ $attachment['name'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Reply Form -->
                @if($ticket->isActive())
                    <div class="p-4 border-t bg-gray-50">
                        <form action="{{ route('support.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <textarea name="message"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]"
                                      placeholder="Écrivez votre réponse..."
                                      required></textarea>

                            <div class="flex items-center justify-between mt-3">
                                <label class="inline-flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-900">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                    Joindre un fichier
                                    <input type="file" name="attachments[]" multiple class="sr-only">
                                </label>
                                <button type="submit"
                                        class="px-6 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700]">
                                    Envoyer
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="p-4 border-t bg-gray-50 text-center text-gray-500">
                        Ce ticket est fermé.
                        <form action="{{ route('support.reopen', $ticket) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-[#CC5A00] hover:underline">Réouvrir</button>
                        </form>
                        pour répondre.
                    </div>
                @endif
            </div>

            <!-- Satisfaction Rating -->
            @if($ticket->status === 'resolved' && !$ticket->satisfaction_rating)
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-6">
                    <h3 class="font-medium text-green-900 mb-3">Comment évaluez-vous notre support ?</h3>
                    <form action="{{ route('support.rate', $ticket) }}" method="POST">
                        @csrf
                        <div class="flex items-center space-x-2 mb-4" x-data="{ rating: 0 }">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button"
                                        x-on:click="rating = {{ $i }}; $refs.ratingInput.value = {{ $i }}"
                                        x-bind:class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'"
                                        class="hover:text-yellow-400 transition-colors">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                            @endfor
                            <input type="hidden" name="rating" x-ref="ratingInput" required>
                        </div>
                        <textarea name="comment"
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                  placeholder="Un commentaire ? (optionnel)"></textarea>
                        <button type="submit"
                                class="mt-3 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Envoyer mon avis
                        </button>
                    </form>
                </div>
            @elseif($ticket->satisfaction_rating)
                <div class="mt-6 bg-gray-50 border rounded-lg p-4">
                    <p class="text-sm text-gray-600">
                        Vous avez noté ce support
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $ticket->satisfaction_rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                        @endfor
                    </p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Info -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Informations</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Créé le</dt>
                        <dd class="font-medium">{{ $ticket->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Priorité</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->priority_color }}-100 text-{{ $ticket->priority_color }}-800">
                                {{ $ticket->priority_label }}
                            </span>
                        </dd>
                    </div>
                    @if($ticket->first_response_at)
                        <div>
                            <dt class="text-gray-500">Première réponse</dt>
                            <dd class="font-medium">{{ $ticket->formatted_response_time }}</dd>
                        </div>
                    @endif
                    @if($ticket->resolved_at)
                        <div>
                            <dt class="text-gray-500">Résolu le</dt>
                            <dd class="font-medium">{{ $ticket->resolved_at->format('d/m/Y à H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Related Booking -->
            @if($ticket->booking)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Réservation liée</h3>
                    <div class="space-y-3">
                        @if($ticket->booking->residence->mainPhoto)
                            <img loading="lazy" src="{{ storage_url($ticket->booking->residence->mainPhoto->path) }}"
                                 alt="{{ $ticket->booking->residence->name }}"
                                 class="w-full h-24 object-cover rounded-lg">
                        @endif
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $ticket->booking->residence->title }}</h4>
                            <p class="text-sm text-gray-600">
                                {{ $ticket->booking->check_in->format('d M') }} - {{ $ticket->booking->check_out->format('d M Y') }}
                            </p>
                        </div>
                        <a href="{{ route('bookings.show', $ticket->booking) }}"
                           class="block text-center px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                            Voir la réservation
                        </a>
                    </div>
                </div>
            @endif

            <!-- Related Dispute -->
            @if($ticket->dispute)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Litige lié</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $ticket->dispute->type_label }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->dispute->status_color }}-100 text-{{ $ticket->dispute->status_color }}-800">
                                {{ $ticket->dispute->status_label }}
                            </span>
                        </div>
                        <a href="{{ route('disputes.show', $ticket->dispute) }}"
                           class="block text-center px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                            Voir le litige
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
