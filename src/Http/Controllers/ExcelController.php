<?php

namespace Maatwebsite\LaravelNovaExcel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelController extends Controller
{
    use ValidatesRequests;

    /**
     * @param Request $request
     * @param ResponseFactory $response
     *
     * @return BinaryFileResponse
     */
    public function download(Request $request, ResponseFactory $response): BinaryFileResponse
    {
        if (!$this->canAccess($request)) {
            abort(404);
        }

        $data = $this->validate($request, [
            'path' => 'required',
            'filename' => 'required',
        ]);

        try {
            $download = $response->download(
                $data['path'],
                $data['filename']
            )->deleteFileAfterSend($shouldDelete = true);
        } catch (\Exception $exception) {
            abort(403);
        }
        return $download;
    }

    /**
     * Check if file can be acessed
     *
     * @param Request $request
     * @return boolean
     */
    protected function canAccess(Request $request): bool
    {
        $canAccess = true;
        $pathInfo = pathinfo($request->input('filename'));

        if (!$this->validatePath($request->input('path'))) {
            $canAccess = false;
        }
        if ($canAccess) {
            if (!$this->checkFileExtension($pathInfo['extension'])) {
                $canAccess = false;
            }
        }
        return $canAccess;
    }

    /**
     * Check if file extension is valid
     *
     * @param string $fileExtension
     * @return boolean
     */
    protected function checkFileExtension(string $fileExtension): bool
    {
        $isValid = true;
        $validFileExtensions = ['csv', 'xlsx', 'xls'];
        if (!in_array($fileExtension, $validFileExtensions)) {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * Check if path is valid
     *
     * @param string $path
     * @return boolean
     */
    protected function validatePath(string $path): bool
    {
        $isValid = false;
        $strRealPath = realpath($path);

        if (strpos($strRealPath, env('LARAVEL_EXCEL_CUSTOM_DOWNLOAD_PATH', base_path() . '/storage')) === 0) {
            $isValid = true;
        }
        return $isValid;
    }
}
