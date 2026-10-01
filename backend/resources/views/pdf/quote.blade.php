<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $reservation->type === 'purchase' ? 'Bon de commande' : 'Devis' }} {{ $reservation->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin-bottom: 0; color: #0b1045; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #0b1045; color: #f0b429; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .label { text-align: right; color: #64748b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; border-bottom: 3px solid #f0b429; padding-bottom: 16px; }
        .brand { color: #0b1045; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $reservation->type === 'purchase' ? 'Bon de commande' : 'Devis' }} {{ $reservation->reference }}</h1>
            <p class="muted">Émis le {{ $reservation->quote_generated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</p>
        </div>
        <div class="brand">
            <strong>La Perle d'Or</strong><br>
            Location & vente de mobilier événementiel<br>
            Lomé, Togo
        </div>
    </div>

    <p>
        <strong>Client :</strong> {{ $reservation->customer->full_name }}<br>
        <strong>Téléphone :</strong> {{ $reservation->customer->phone }}<br>
        <strong>Email :</strong> {{ $reservation->customer->email }}
    </p>

    <p>
        @if($reservation->type === 'rental')
            <strong>Période de l'événement :</strong>
            {{ $reservation->event_start_date->format('d/m/Y') }} au {{ $reservation->event_end_date->format('d/m/Y') }}<br>
        @endif
        <strong>Mode :</strong> {{ $reservation->delivery_method === 'delivery' ? 'Livraison' : 'Retrait au dépôt' }}
        @if($reservation->delivery_address)
            — {{ $reservation->delivery_address }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th>Quantité</th>
                <th>{{ $reservation->type === 'purchase' ? 'Prix unitaire' : 'Prix / jour' }}</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @php($hasPendingPrice = false)
            @foreach($reservation->items as $line)
                @php($priceOnRequest = $reservation->type === 'purchase' && $line->unit_sale_price === null)
                @php($hasPendingPrice = $hasPendingPrice || $priceOnRequest)
                <tr>
                    <td>{{ $line->item?->name_fr }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>
                        @if($priceOnRequest)
                            Sur devis
                        @else
                            {{ number_format((float) ($line->unit_sale_price ?? $line->unit_price_per_day), 0, ',', ' ') }} {{ $reservation->currency }}
                        @endif
                    </td>
                    <td>
                        @if($priceOnRequest)
                            À confirmer
                        @else
                            {{ number_format((float) $line->subtotal, 0, ',', ' ') }} {{ $reservation->currency }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Sous-total</td>
            <td>{{ number_format((float) $reservation->subtotal, 0, ',', ' ') }} {{ $reservation->currency }}</td>
        </tr>
        @if($reservation->discount > 0)
            <tr>
                <td class="label">Remise</td>
                <td>- {{ number_format((float) $reservation->discount, 0, ',', ' ') }} {{ $reservation->currency }}</td>
            </tr>
        @endif
        <tr>
            <td class="label"><strong>Total</strong></td>
            <td><strong>{{ number_format((float) $reservation->total, 0, ',', ' ') }} {{ $reservation->currency }}</strong></td>
        </tr>
    </table>

    @if($hasPendingPrice)
        <p><em>* Le prix des articles "Sur devis" n'est pas inclus dans le total ci-dessus et vous sera communiqué séparément par notre équipe.</em></p>
    @endif

    @if($reservation->notes)
        <p><strong>Notes :</strong> {{ $reservation->notes }}</p>
    @endif

    <p class="muted" style="margin-top: 32px;">
        @if($reservation->type === 'purchase')
            Merci pour votre commande. Nous vous contacterons pour confirmer la livraison ou le retrait, en indiquant la référence {{ $reservation->reference }}.
        @else
            Ce devis est valable 48 heures. Pour confirmer votre réservation, contactez-nous en indiquant la référence {{ $reservation->reference }}.
        @endif
    </p>
</body>
</html>
