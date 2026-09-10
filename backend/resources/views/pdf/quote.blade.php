<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $reservation->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .label { text-align: right; color: #64748b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Devis {{ $reservation->reference }}</h1>
            <p class="muted">Émis le {{ $reservation->quote_generated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</p>
        </div>
        <div>
            <strong>EventLoc</strong><br>
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
        <strong>Période de l'événement :</strong>
        {{ $reservation->event_start_date->format('d/m/Y') }} au {{ $reservation->event_end_date->format('d/m/Y') }}<br>
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
                <th>Prix / jour</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservation->items as $line)
                <tr>
                    <td>{{ $line->item?->name_fr }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format((float) $line->unit_price_per_day, 0, ',', ' ') }} {{ $reservation->currency }}</td>
                    <td>{{ number_format((float) $line->subtotal, 0, ',', ' ') }} {{ $reservation->currency }}</td>
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
        <tr>
            <td class="label">Caution demandée</td>
            <td>{{ number_format((float) $reservation->deposit_required, 0, ',', ' ') }} {{ $reservation->currency }}</td>
        </tr>
    </table>

    @if($reservation->notes)
        <p><strong>Notes :</strong> {{ $reservation->notes }}</p>
    @endif

    <p class="muted" style="margin-top: 32px;">
        Ce devis est valable 48 heures. Pour confirmer votre réservation, contactez-nous en indiquant la référence {{ $reservation->reference }}.
    </p>
</body>
</html>
