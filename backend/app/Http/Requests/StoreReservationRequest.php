<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Guests (no Sanctum token) must supply their own contact details;
        // signed-in customers already have them on file.
        $isGuest = $this->user('sanctum') === null;

        return [
            'type' => ['required', 'in:rental,purchase'],

            'event_start_date' => ['required_if:type,rental', 'nullable', 'date', 'after_or_equal:today'],
            'event_end_date' => ['required_if:type,rental', 'nullable', 'date', 'after_or_equal:event_start_date'],

            'delivery_method' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:255'],
            'delivery_slot_id' => ['nullable', 'integer', 'exists:delivery_slot_templates,id'],
            'return_slot_id' => ['nullable', 'integer', 'exists:delivery_slot_templates,id'],

            'notes' => ['nullable', 'string', 'max:1000'],
            'promo_code' => ['nullable', 'string', 'max:50'],

            'customer' => [$isGuest ? 'required' : 'nullable', 'array'],
            'customer.first_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:100'],
            'customer.last_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:100'],
            'customer.email' => [$isGuest ? 'required' : 'nullable', 'email', 'max:255'],
            'customer.phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:30'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') !== 'rental') {
                return;
            }

            $start = $this->input('event_start_date');
            $end = $this->input('event_end_date');

            if ($start && $end && Carbon::parse($start)->diffInDays(Carbon::parse($end)) > 60) {
                $validator->errors()->add('event_end_date', 'La période de réservation ne peut pas dépasser 60 jours.');
            }
        });
    }
}
