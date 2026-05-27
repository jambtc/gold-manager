<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class SwaggerController extends Controller
{
    public function actionIndex(): string
    {
        $this->layout = 'swagger_layout';

        return $this->render('index', [
            'specUrl' => Url::to(['/swagger/openapi'], true),
        ]);
    }

    public function actionOpenapi(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (class_exists(\OpenApi\Generator::class)) {
            $openapi = \OpenApi\Generator::scan([__DIR__ . '/api/v1']);
            return $this->asJson(json_decode($openapi->toJson(), true));
        }

        return $this->asJson($this->buildFallbackSpec());
    }

    public function actionGenerate(): string
    {
        $this->layout = 'swagger_layout';

        $targetDir = Yii::getAlias('@runtime/openapi');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $target = $targetDir . '/swagger.json';
        $source = 'fallback-builder';
        $warning = null;
        $spec = $this->buildFallbackSpec();

        if (class_exists(\OpenApi\Generator::class)) {
            $openapi = \OpenApi\Generator::scan([__DIR__ . '/api/v1']);
            $spec = json_decode($openapi->toJson(), true);
            $source = 'swagger-php-scan';
        } else {
            $warning = 'Package zircote/swagger-php non disponibile: generato file fallback.';
        }

        file_put_contents($target, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->render('generate', [
            'ok' => true,
            'source' => $source,
            'file' => $target,
            'openapiUrl' => Url::to(['/swagger/openapi'], true),
            'warning' => $warning,
        ]);
    }

    private function buildFallbackSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Gold Manager API',
                'version' => '1.0.0',
                'description' => 'API pubbliche versionate per integrazioni web/mobile.',
            ],
            'servers' => [
                ['url' => Yii::$app->request->hostInfo],
            ],
            'paths' => [
                '/api/v1/user/status' => [
                    'get' => [
                        'summary' => 'Stato onboarding utente',
                        'description' => 'Restituisce stato preparazione squadra dell\'utente autenticato.',
                        'operationId' => 'getUserStatusV1',
                        'tags' => ['User'],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/UserStatusResponse',
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Utente non autenticato',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'UserStatusResponse' => [
                        'type' => 'object',
                        'required' => ['ready', 'status'],
                        'properties' => [
                            'ready' => ['type' => 'boolean'],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['pending', 'active', 'error'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

