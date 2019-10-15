<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Util\FormatUtil;

/**
 * Error Factory
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class ErrorFactory
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator){
        $this->translator = $translator;
    }
    
    public function accessDenied(Request $request, $message = null, $details = null){
        return self::factory($request, 403, $message);
    }
    
    public function notFound(Request $request, $message = null, $details = null){
        return self::factory($request, 404, $message);
    }
    
    public function badRequest(Request $request, $message = null, $details = null) {
        return self::factory($request, 400, $message);
    }
    
    public function requiredField($field) {
        return $this->translator->trans('message.400.required_field', [
            '%field%' => $field
        ], 'error');
    }
    
    public function invalidTypeValue($field, $value, $type) {
        return $this->translator->trans('message.400.invalid_type_value', [
            '%field%' => $field,
            '%value%' => $value,
            '%type%' => $type
        ], 'error');
    }
    
    public function invalidEntityValue($field, $value) {
        return $this->translator->trans('message.400.invalid_entity_value', [
            '%field%' => $field,
            '%value%' => $value
        ], 'error');
    }
    
    public function factory(Request $request, $code, $message = null){
        $message = $message ?? $this->translator->trans('message.'.$code.'.default', [], 'error');
        $response = ['code' => $code, 'message' => $message];
        return FormatUtil::formatView($request, $response, $code);
    }
    
}