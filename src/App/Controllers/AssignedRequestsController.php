<?php

namespace App\Controllers;

use App\Models\AssignedRequests;
use App\Models\Onboarding;
use App\Services\AssignedDocumentService;
use App\Services\Auth;
use RuntimeException;

/**
 * Assigned Requests
 *
 * The employee side of the requests HR or Administration assigns to
 * them. These are HR's onboarding document requests, viewed and fulfilled
 * from the employee's own page:
 *
 *   HR requests in Onboarding -> employee submits here -> HR verifies
 *   in Onboarding.
 *
 * There is no separate HR screen: HR already has the Onboarding module to
 * create, verify and reject these requests.
 *
 * The employee's own employee_id (from the session) is always used to
 * scope reads and writes — never a value supplied by the client — so an
 * employee cannot see or touch another employee's requests.
 */
class AssignedRequestsController
{
    private AssignedRequests $assignedRequests;

    private AssignedDocumentService $documents;

    public function __construct()
    {
        $this->assignedRequests = new AssignedRequests();
        $this->documents = new AssignedDocumentService();
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — ASSIGNED REQUESTS PAGE
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        Auth::requireRole(['EMP']);

        $employeeId = $this->requireEmployeeId();

        $requests = $this->assignedRequests
            ->getRequestsForEmployee($employeeId);


        /*
        |--------------------------------------------------------------------------
        | Per-request decorations, computed in a single pass
        |--------------------------------------------------------------------------
        |
        | has_file      — whether the submitted file is actually on disk, so
        |                 the page never renders a dead download link
        | activities    — the timeline, derived from the row's timestamps
        |
        */

        $activitiesByRequest = [];

        foreach ($requests as &$request) {

            $storedName = trim(
                (string) ($request['submission_file'] ?? '')
            );

            $request['has_file'] =
                $storedName !== ''
                && $this->documents->resolve($storedName) !== null;

            $activitiesByRequest[(int) $request['request_id']] =
                $this->assignedRequests->buildTimeline($request);
        }

        unset($request);


        /*
         * Shared with the Onboarding "Request Document" form, so the
         * types HR can pick and the icons shown here cannot drift apart.
         */
        $requestTypes = Onboarding::REQUEST_TYPES;

        require BASE_PATH . '/resources/views/assigned-requests/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — SUBMIT RESPONSE / DOCUMENT / CONFIRMATION
    |--------------------------------------------------------------------------
    |
    | The request's own action_type decides what is required. Validation is
    | done here, never trusted from the client.
    |
    */

    public function submit(): void
    {
        Auth::requireRole(['EMP']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('assigned-requests');
        }

        $employeeId = $this->requireEmployeeId();

        $requestId = filter_input(
            INPUT_POST,
            'request_id',
            FILTER_VALIDATE_INT
        );

        if (!$requestId) {
            $this->redirect(
                'assigned-requests',
                ['error' => 'invalid']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Load the request — ownership is part of the query
        |--------------------------------------------------------------------------
        */

        $request = $this->assignedRequests
            ->getRequestForEmployee($requestId, $employeeId);

        if (!$request) {
            $this->redirect(
                'assigned-requests',
                ['error' => 'not-found']
            );
        }

        if (($request['status'] ?? '') === 'Completed') {
            $this->redirect(
                'assigned-requests',
                ['error' => 'already-completed']
            );
        }

        $actionType = (string) ($request['action_type'] ?? 'document');

        $responseText = trim(
            (string) ($_POST['response'] ?? '')
        );

        if (mb_strlen($responseText) > 2000) {
            $this->redirect(
                'assigned-requests',
                ['error' => 'response-length']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Required input per action type
        |--------------------------------------------------------------------------
        */

        if (
            $actionType === 'response'
            && $responseText === ''
        ) {
            $this->redirect(
                'assigned-requests',
                ['error' => 'response-required']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Document upload (validated + stored by the service)
        |--------------------------------------------------------------------------
        */

        $file = null;

        $upload = $_FILES['document'] ?? null;

        $hasUpload =
            is_array($upload)
            && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        $existingFile = trim(
            (string) ($request['submission_file'] ?? '')
        );


        /*
         * A document request only needs a file when nothing has been
         * submitted yet — replacing an existing submission is optional.
         */
        if (
            $actionType === 'document'
            && !$hasUpload
            && $existingFile === ''
        ) {
            $this->redirect(
                'assigned-requests',
                ['error' => 'document-required']
            );
        }

        if ($hasUpload) {

            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $this->redirect(
                    'assigned-requests',
                    ['error' => 'upload']
                );
            }

            try {

                $file = $this->documents->store(
                    $upload,
                    $requestId
                );
            } catch (RuntimeException $e) {

                $this->redirect(
                    'assigned-requests',
                    [
                        'error' => 'upload',
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Persist the submission
        |--------------------------------------------------------------------------
        */

        $result = $this->assignedRequests->submit(
            $requestId,
            $employeeId,
            (int) Auth::userId(),
            $responseText !== '' ? $responseText : null,
            $file
        );

        if (!$result['success']) {

            /*
             * Do not leave an orphaned file on disk when the write failed.
             */
            if ($file !== null) {
                $this->documents->delete($file['stored_name']);
            }

            $this->redirect(
                'assigned-requests',
                ['error' => 'submit']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove the file this submission replaced, if any
        |--------------------------------------------------------------------------
        */

        if (
            $file !== null
            && $existingFile !== ''
            && $existingFile !== $file['stored_name']
        ) {
            $this->documents->delete($existingFile);
        }


        $this->redirect(
            'assigned-requests',
            ['success' => 'submitted']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD SUBMITTED DOCUMENT
    |--------------------------------------------------------------------------
    |
    | An employee may download only their own submission; HR, Manager and
    | Admin may download any.
    |
    */

    public function download(): void
    {
        Auth::requireLogin();

        $requestId = filter_input(
            INPUT_GET,
            'request_id',
            FILTER_VALIDATE_INT
        );

        if (!$requestId) {
            http_response_code(404);
            exit('Document not found.');
        }

        $request = null;

        $employeeId = Auth::employeeId();

        if ($employeeId) {
            $request = $this->assignedRequests
                ->getRequestForEmployee($requestId, $employeeId);
        }

        if (
            !$request
            && Auth::hasAnyRole(['ADMIN', 'HR', 'MGR'])
        ) {
            $request = $this->assignedRequests
                ->getRequestById($requestId);
        }

        $storedName = $request['submission_file'] ?? null;

        if (!$request || !$storedName) {
            http_response_code(404);
            exit('Document not found.');
        }

        $path = $this->documents->resolve($storedName);

        if ($path === null) {
            http_response_code(404);
            exit('The submitted document is no longer available.');
        }

        header('Content-Type: ' . (
            $request['submission_mime']
            ?: 'application/octet-stream'
        ));

        header(
            'Content-Disposition: attachment; filename="'
            . addslashes($this->downloadFileName($request, $storedName))
            . '"'
        );

        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');

        readfile($path);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * A friendly download name built from the request title, since the
     * stored name is an opaque generated filename.
     */
    private function downloadFileName(
        array $request,
        string $storedName
    ): string {

        $extension = pathinfo($storedName, PATHINFO_EXTENSION);

        $base = preg_replace(
            '/[^A-Za-z0-9 _-]+/',
            ' ',
            (string) ($request['title'] ?? 'document')
        );

        $base = trim(
            preg_replace('/\s+/', ' ', (string) $base)
        );

        if ($base === '') {
            $base = 'document';
        }

        return $extension !== ''
            ? $base . '.' . $extension
            : $base;
    }


    /**
     * The signed-in employee's own record, or a hard stop.
     */
    private function requireEmployeeId(): int
    {
        $employeeId = Auth::employeeId();

        if (!$employeeId) {

            http_response_code(403);

            exit(
                'Your account is not connected to an employee record.'
            );
        }

        return $employeeId;
    }


    /**
     * Redirect back to a page with optional query feedback, matching the
     * existing controller convention.
     */
    private function redirect(
        string $page,
        array $params = []
    ): void {

        $url = url('?page=') . urlencode($page);

        if ($params) {
            $url .= '&' . http_build_query($params);
        }

        header('Location: ' . $url);

        exit;
    }
}
