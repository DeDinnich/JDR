<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\SessionExtractionRequest;
use App\Services\Campaign\SessionExtractionService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SessionExtractionController extends Controller
{
    public function __invoke(
        SessionExtractionRequest $request,
        SessionExtractionService $service,
    ): StreamedResponse {
        $snapshot = $service->extract($request->validated('user_ids'));
        $filename = 'extraction-session-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(
            static function () use ($snapshot): void {
                echo json_encode(
                    $snapshot,
                    JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_INVALID_UTF8_SUBSTITUTE
                        | JSON_THROW_ON_ERROR,
                );
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }
}
