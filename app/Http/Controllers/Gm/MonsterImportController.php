<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\MonsterImportRequest;
use App\Services\Bestiary\MonsterImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonsterImportController extends Controller
{
    public function show(MonsterImportService $service): View
    {
        return $this->view($service, '', null);
    }

    public function analyse(MonsterImportRequest $request, MonsterImportService $service): View
    {
        $json = (string) $request->input('json');

        return $this->view($service, $json, ['mode' => 'analyse'] + $service->analyse($json));
    }

    public function store(MonsterImportRequest $request, MonsterImportService $service): View|RedirectResponse
    {
        $json = (string) $request->input('json');
        $result = $service->import($json);

        if (! $result['ok']) {
            return $this->view($service, $json, ['mode' => 'import'] + $result);
        }

        $message = $result['imported'].' monstre(s) importé(s).';

        if ($result['duplicates'] !== []) {
            $message .= ' '.count($result['duplicates']).' doublon(s) ignoré(s).';
        }

        return redirect()->route('gm.bestiary.index')->with('success', $message);
    }

    private function view(MonsterImportService $service, string $json, ?array $result): View
    {
        return view('gm.bestiary.import', [
            'json' => $json,
            'result' => $result,
            'example' => $service->example(),
        ]);
    }
}
