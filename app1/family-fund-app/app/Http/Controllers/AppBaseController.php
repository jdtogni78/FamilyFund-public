<?php

namespace App\Http\Controllers;

use InfyOm\Generator\Utils\ResponseUtil;
use Response;

/**
 * @SWG\Swagger(
 *   basePath="/api/v1",
 *   @SWG\Info(
 *     title="Laravel Generator APIs",
 *     version="1.0.0",
 *   )
 * )
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $message)
    {
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    public function sendError($error, $code = 404)
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    public function sendSuccess($message)
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    
//     /**
//      * success response method.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function sendResponse($result, $message, $code)
//     {
//         $response = [
//             'success' => true,
//             'data'    => $result,
//             'message' => $message,
//         ];
//         return response()->json($response, $code);
//     }


//     /**
//      * return error response.
//      *
//      * @return \Illuminate\Http\Response
//      */
//     public function sendError($error, $errorMessages = [], $code)
//     {
//         $response = [
//             'success' => false,
//             'message' => $error,
//         ];
//         if (!empty($errorMessages)) {
//             $response['data'] = $errorMessages;
//         }
//         return response()->json($response, $code);
//     }
}
