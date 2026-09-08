@extends('layouts.app')

@section('titre', 'Corbeille')
@section('sous-titre', $compteurs->sum().' élément(s) supprimé(s), tous établissements confondus')

@section('actions-entete')
    <a href="{{ route('admin.plateforme.parametres') }}" class="bouton-secondaire">Durée de conservation</a>
@endsection

@section('contenu')

@php
    $limite = now()->subDays($jours);
    $expires = $lignes->filter(fn ($l) => $l->deleted_at && $l->deleted_at->lt($limite));
@endphp

    <div class="flex items-start gap-3 rounded-xl border border-ogar-200 bg-ogar-50 px-5 py-3 text-sm text-ogar-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span>
            Une fiche supprimée reste ici avec tout ce qui s’y rattache — notes, inscriptions, présences.
            La restaurer la rend intacte. La supprimer définitivement est <strong>sans retour</strong>.
            Conservation réglée à {{ $jours }} jour(s).
        </span>
    </div>

    {{-- ----------------------------------------------------------------
         Un onglet par type d'élément
         ---------------------------------------------------------------- --}}
    <div class="carte mt-6 overflow-hidden">
        <div class="flex flex-wrap gap-1 border-b border-gris-100 bg-gris-50 px-3 pt-3">
            @foreach ($entites as $cle => $onglet)
                <a href="{{ route('admin.plateforme.corbeille', ['entite' => $cle]) }}"
                   class="flex items-center gap-2 rounded-t-lg border border-b-0 px-4 py-2 text-sm font-medium transition
                          {{ $ouverte === $cle
                             ? 'border-gris-200 bg-white text-ogar-700'
                             : 'border-transparent text-gris-500 hover:text-gris-800' }}">
                    {{ $onglet['libelle'] }}
                    <span class="rounded-full px-2 py-0.5 text-[11px] tabular-nums
                                 {{ $ouverte === $cle ? 'bg-ogar-50 text-ogar-700' : 'bg-gris-200 text-gris-600' }}">
                        {{ $compteurs[$cle] }}
                    </span>
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gris-100 px-5 py-3">
            <p class="text-xs text-gris-500">
                {{ $lignes->total() }} {{ mb_strtolower($definition['libelle']) }} en corbeille
                @if ($expires->isNotEmpty())
                    &middot; <span class="text-corail-600">{{ $expires->count() }} au-delà de {{ $jours }} jours</span>
                @endif
            </p>

            @if ($lignes->total() > 0)
                <x-confirmation :action="route('admin.plateforme.corbeille.vider', $ouverte)" methode="POST"
                                titre="Vider les éléments expirés ?"
                                :message="'Seuls les éléments supprimés depuis plus de '.$jours.' jours seront détruits. Cette action est sans retour.'"
                                confirmer="Vider"
                                bouton="bouton-secondaire">
                    Vider les expirés
                </x-confirmation>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Élément</th>
                        <th>Référence</th>
                        <th>Établissement</th>
                        <th>Supprimé le</th>
                        <th>Par</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        @php($expire = $ligne->deleted_at && $ligne->deleted_at->lt($limite))

                        <tr>
                            <td class="font-medium text-gris-800">
                                {{ ($definition['nom'])($ligne) ?: '—' }}
                                @if ($expire)
                                    <span class="ml-1 rounded bg-corail-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-corail-600">
                                        Expiré
                                    </span>
                                @endif
                            </td>
                            <td class="font-mono text-xs text-gris-500">{{ ($definition['reference'])($ligne) ?: '—' }}</td>
                            <td class="text-gris-600">{{ $ecoles[$ligne->school_id] ?? '—' }}</td>
                            <td class="whitespace-nowrap text-gris-600">
                                {{ $ligne->deleted_at?->locale('fr')->isoFormat('D MMM YYYY à HH:mm') ?? '—' }}
                                <span class="block text-[11px] text-gris-400">
                                    {{ $ligne->deleted_at?->diffForHumans() }}
                                </span>
                            </td>
                            <td class="text-gris-600">{{ $auteurs[$ligne->deleted_by] ?? '—' }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <form method="POST"
                                          action="{{ route('admin.plateforme.corbeille.restaurer', [$ouverte, $ligne->id]) }}">
                                        @csrf
                                        <button type="submit" class="bouton-mini">Restaurer</button>
                                    </form>

                                    <x-confirmation :action="route('admin.plateforme.corbeille.supprimer', [$ouverte, $ligne->id])"
                                                    methode="DELETE"
                                                    titre="Supprimer définitivement ?"
                                                    :message="($definition['nom'])($ligne).' et tout ce qui s’y rattache seront détruits. Cette action est sans retour.'"
                                                    confirmer="Supprimer définitivement"
                                                    bouton="bouton-mini border-corail-300 text-corail-700 hover:bg-corail-50">
                                        Supprimer
                                    </x-confirmation>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6"
                                :message="'Aucun élément de type « '.mb_strtolower($definition['libelle']).' » en corbeille.'"/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lignes->hasPages())
            <div class="border-t border-gris-100 px-5 py-3">
                {{ $lignes->links() }}
            </div>
        @endif
    </div>

@endsection
