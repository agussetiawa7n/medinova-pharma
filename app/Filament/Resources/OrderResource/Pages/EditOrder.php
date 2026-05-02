<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $oldPaymentStatus = $record->payment_status;

        $newStatus = OrderStatus::tryFrom($data['status'] ?? '');
        $newPaymentStatus = PaymentStatus::tryFrom($data['payment_status'] ?? '');

        unset($data['status'], $data['payment_status']);

        $record->fill($data);
        $record->save();

        $orderService = app(OrderService::class);
        $adminNote = $data['notes'] ?? '';

        if ($newStatus && $newStatus !== $oldStatus) {
            if ($newStatus === OrderStatus::Cancelled) {
                $orderService->cancelOrder(
                    $record,
                    $adminNote ?: 'Cancelled by admin.'
                );
                Notification::make()
                    ->title('Order cancelled — stock restored, wallet refunded if applicable.')
                    ->success()
                    ->send();
            } else {
                $orderService->updateStatus(
                    $record,
                    $newStatus->value,
                    $adminNote ?: "Status changed to {$newStatus->label()} by admin.",
                    Auth::id()
                );
                Notification::make()
                    ->title("Order status updated to {$newStatus->label()}.")
                    ->success()
                    ->send();
            }
        }

        if (
            $newPaymentStatus
            && $newPaymentStatus !== $oldPaymentStatus
            && $newPaymentStatus === PaymentStatus::Paid
        ) {
            $orderService->markPaid($record, 'admin_manual');
        }

        return $record;
    }
}
