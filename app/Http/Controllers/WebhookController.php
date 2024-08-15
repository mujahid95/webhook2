<?php

namespace App\Http\Controllers;

use App\Utilities\Constant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected string $webHookSecretKey;

    public function __construct()
    {
        $this->webHookSecretKey = env('GITHUB_WEBHOOK_SECRET');
    }

    public function handle(Request $request)
    {
        try {
            // Get the X-Hub-Signature header
            $signature = $request->header('X-Hub-Signature');

            // Compute the HMAC hex digest
            $payload = $request->getContent();

            // Compute the expected HMAC signature
            $computedSignature = 'sha1=' . hash_hmac('sha1', $payload, $this->webHookSecretKey);

            // This is pull request code

            // Verify if the signature matches
            if (!hash_equals($computedSignature, $signature)) {
                Log::warning('Invalid signature', ['signature' => $signature, 'computed' => $computedSignature]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Handle the webhook payload
            $payload = $request->all();

            if ($request->header('X-GitHub-Event') === Constant::PRType['open']) {
                $action = $payload['action'] ?? null;

                // If the PR is opened
                if ($action === Constant::PRAction['opened']) {
                    $pullRequest = $payload['pull_request'];
                    $pullRequestNumber = $pullRequest['number'];
                    $repoFullName = $pullRequest['base']['repo']['full_name'];
                    $action = $payload['action'];


                    // Extract details
                    $branchName = $pullRequest['head']['ref'];
                    $baseBranch = $pullRequest['base']['ref'];
                    $userName = $pullRequest['user']['login'];
                    $title = $pullRequest['title'];

                    // Fetch pull request files

//                    $filesResponse = Http::withToken(env('GITHUB_TOKEN'))
//                        ->get("https://github.com/{$userName}/pulls/{$pullRequestNumber}/files");
//
//                    if ($filesResponse->successful()) {
//                        $files = $filesResponse->json();
//                        Log::info('Changed files in the pull request:', [$files]);
//                    } else {
//                        Log::error('Failed to fetch pull request files:', [$filesResponse->body()]);
//                    }


                    // Log details
                    Log::info('Pull Request Event:', [
                        'action' => $action,
                        'branch_name' => $branchName,
                        'base_branch' => $baseBranch,
                        'user_name' => $userName,
                        'title' => $title,
                        'pullRequestNumber' => $pullRequest['number'],
                        'repoFullName' => $pullRequest['base']['repo']['full_name'],
                        'base_repo' => $pullRequest['base']['repo'],
                        'htmlUrl' => $pullRequest['html_url'],
                    ]);
                }
            }


        } catch (Exception $exception) {
            Log::error('in webhook catch => ', [$exception->getMessage()]);
        }
    }
}
