<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Models\NewsletterSubscriber;

new class extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';
    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate();
        NewsletterSubscriber::firstOrCreate(
            ['email' => $this->email],
            ['subscribed_at' => now(), 'is_active' => true]
        );
        $this->subscribed = true;
        $this->email = '';
    }
};
?>

<div>
    @if($subscribed)
    <div class="flex items-center gap-2 text-green-400 text-sm font-medium py-2">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        You are subscribed!
    </div>
    @else
    <form wire:submit="subscribe" class="flex gap-2">
        <input wire:model="email" type="email" placeholder="your@email.com"
               class="flex-1 px-3 py-2 rounded-full bg-gray-800 text-white text-sm placeholder-gray-500 border border-gray-700 focus:outline-none focus:border-brand-400">
        <button type="submit" class="px-4 py-2 rounded-full text-white text-sm font-medium whitespace-nowrap" style="background:linear-gradient(135deg,#FF647B,#FA4E67)">
            Subscribe
        </button>
    </form>
    @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
    @endif
</div>
