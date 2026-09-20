<?php

namespace App\Services\Research;

use App\Models\Portal\PartnershipRequest;
use App\Models\Portal\PartnershipRequestDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnershipInquiryService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function submit(array $data, array $files = []): PartnershipRequest
    {
        return DB::transaction(function () use ($data, $files) {
            $type = $data['applicant_type'] === 'individual' ? 'individual' : 'organisation';
            $first = trim((string) ($data['first_name'] ?? ''));
            $last = trim((string) ($data['last_name'] ?? ''));
            $contact = trim($first.' '.$last) ?: null;

            $request = PartnershipRequest::query()->create([
                'request_number' => $this->nextRequestNumber(),
                'applicant_type' => $type,
                'first_name' => $first ?: null,
                'last_name' => $last ?: null,
                'organization_name' => $type === 'organisation'
                    ? ($data['organization_name'] ?? null)
                    : ($contact ?: 'Individual'),
                'organisation_details' => $type === 'organisation' ? ($data['organisation_details'] ?? null) : null,
                'individual_details' => $type === 'individual' ? ($data['individual_details'] ?? null) : null,
                'organization_type' => $type === 'organisation'
                    ? ($data['organization_type'] ?? 'ngo')
                    : 'individual',
                'contact_person' => $contact,
                'email' => $data['email'],
                'alternative_email' => $data['alternative_email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alternative_phone' => $data['alternative_phone'] ?? null,
                'research_area' => $data['research_area'] ?? null,
                'what_they_do' => $data['what_they_do'] ?? null,
                'why_partnership' => $data['why_partnership'] ?? null,
                'proposed_scope' => $data['why_partnership'] ?? $data['what_they_do'] ?? null,
                'status' => 'pending_review',
                'created_at' => now(),
            ]);

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $path = $file->store('partnership-requests/'.$request->id, 'local');
                PartnershipRequestDocument::query()->create([
                    'partnership_request_id' => $request->id,
                    'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'created_at' => now(),
                ]);

                if (! $request->supporting_document_path) {
                    $request->supporting_document_path = $path;
                    $request->save();
                }
            }

            return $request->fresh('documents');
        });
    }

    private function nextRequestNumber(): string
    {
        do {
            $number = 'PR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (PartnershipRequest::query()->where('request_number', $number)->exists());

        return $number;
    }
}
