<?php

namespace App\Services\Requests;

use App\Enums\EnquiryStatus;
use App\Models\ContactEnquiry;
use App\Models\ContactEnquiryNote;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EnquiryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForVehicle(
        ?Vehicle $vehicle,
        array $data,
        ?string $ip = null,
        string $source = 'web'
    ): Enquiry {
        $enquiry = Enquiry::create([
            'vehicle_id' => $vehicle?->id,
            'name' => trim((string) $data['name']),
            'phone' => $this->phone($data['phone'] ?? null),
            'email' => $data['email'] ?? null,
            'message' => trim((string) $data['message']),
            'status' => EnquiryStatus::NEW,
            'source' => $source,
            'ip_address' => $ip,
        ]);

        $this->log($enquiry, 'enquiry_received', [
            'vehicle_id' => $vehicle?->id,
            'source' => $source,
        ]);

        return $enquiry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createContact(array $data, ?string $ip = null, string $source = 'web'): ContactEnquiry
    {
        $enquiry = ContactEnquiry::create([
            'name' => trim((string) $data['name']),
            'phone' => $this->phone($data['phone'] ?? null),
            'email' => $data['email'] ?? null,
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'status' => EnquiryStatus::NEW,
            'ip_address' => $ip,
        ]);

        $this->log($enquiry, 'contact_enquiry_received', ['source' => $source]);

        return $enquiry;
    }

    public function updateVehicleEnquiryStatus(Enquiry $enquiry, EnquiryStatus $status, ?User $user = null): Enquiry
    {
        $enquiry->update(['status' => $status]);
        $this->log($enquiry, 'status_changed', ['status' => $status->value], $user);

        return $enquiry;
    }

    public function addVehicleEnquiryNote(Enquiry $enquiry, string $note, ?User $user = null): EnquiryNote
    {
        $note = $enquiry->notes()->create([
            'user_id' => $user?->id,
            'note' => $note,
        ]);

        $this->log($enquiry, 'note_added', [], $user);

        return $note;
    }

    public function updateContactStatus(ContactEnquiry $enquiry, EnquiryStatus $status, ?User $user = null): ContactEnquiry
    {
        $enquiry->update(['status' => $status]);
        $this->log($enquiry, 'status_changed', ['status' => $status->value], $user);

        return $enquiry;
    }

    public function addContactNote(ContactEnquiry $enquiry, string $note, ?User $user = null): ContactEnquiryNote
    {
        $note = $enquiry->notes()->create([
            'user_id' => $user?->id,
            'note' => $note,
        ]);

        $this->log($enquiry, 'note_added', [], $user);

        return $note;
    }

    private function phone(mixed $value): string
    {
        $normalized = PhoneNumber::normalize(is_string($value) ? $value : null);

        if (! PhoneNumber::isValidIndianMobile($normalized)) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid 10-digit Indian mobile number.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Enquiry|ContactEnquiry $subject, string $event, array $properties = [], ?User $user = null): void
    {
        $activity = activity('enquiry')->performedOn($subject);

        if ($user) {
            $activity->causedBy($user);
        }

        $activity->withProperties(Arr::add($properties, 'event', $event))->log($event);
    }
}
