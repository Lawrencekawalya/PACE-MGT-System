<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\ReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('purchase_order');

        return $order instanceof PurchaseOrder
            && $this->user()?->can('export', $order) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'format' => ['required', Rule::enum(ReportFormat::class)],
        ];
    }
}
