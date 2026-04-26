<?php

namespace App\Livewire;

use App\Models\NewsletterSubscriber;
use Livewire\Component;

class NewsletterForm extends Component
{
    public string $email = '';

    public bool $submitted = false;

    protected array $rules = [
        'email' => 'required|email|max:255',
    ];

    public function subscribe(): void
    {
        $this->validate();

        NewsletterSubscriber::firstOrCreate(
            ['email' => $this->email],
            ['is_active' => true]
        );

        $this->submitted = true;
        $this->email = '';
    }

    public function render()
    {
        return view('livewire.newsletter-form');
    }
}
