<?php

declare(strict_types=1);

namespace app\controllers\api\v1;

use OpenApi\Annotations as OA;
use yii\web\Controller;

/**
 * @OA\Info(
 *   title="Gold Manager REST API",
 *   version="v1.0.0",
 *   description="REST API per integrazioni web/mobile di Gold Manager."
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="SessionCookie",
 *   type="apiKey",
 *   in="cookie",
 *   name="_identity",
 *   description="Sessione browser Yii2 autenticata."
 * )
 *
 * @OA\Tag(
 *   name="User",
 *   description="Operazioni utente."
 * )
 */
class ApiController extends Controller
{
}

