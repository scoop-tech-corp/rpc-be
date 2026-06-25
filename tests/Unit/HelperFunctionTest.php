<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\JsonResponse;

class HelperFunctionTest extends TestCase
{
    // ─── convertTrueFalse ────────────────────────────────────────────────────

    public function test_convertTrueFalse_returns_1_for_true_string()
    {
        $this->assertEquals(1, convertTrueFalse('true'));
    }

    public function test_convertTrueFalse_returns_1_for_TRUE_uppercase()
    {
        $this->assertEquals(1, convertTrueFalse('TRUE'));
    }

    public function test_convertTrueFalse_returns_0_for_false_string()
    {
        $this->assertEquals(0, convertTrueFalse('false'));
    }

    public function test_convertTrueFalse_returns_0_for_FALSE_uppercase()
    {
        $this->assertEquals(0, convertTrueFalse('FALSE'));
    }

    public function test_convertTrueFalse_returns_null_for_other_values()
    {
        $this->assertNull(convertTrueFalse('yes'));
        $this->assertNull(convertTrueFalse('1'));
        $this->assertNull(convertTrueFalse(''));
        $this->assertNull(convertTrueFalse('random'));
    }

    // ─── responseCreate ──────────────────────────────────────────────────────

    public function test_responseCreate_returns_200_with_correct_message()
    {
        $response = responseCreate();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Add Data Successful!', $response->getData()->message);
    }

    // ─── responseUpdate ──────────────────────────────────────────────────────

    public function test_responseUpdate_returns_200_with_correct_message()
    {
        $response = responseUpdate();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Update Data Successful', $response->getData()->message);
    }

    // ─── responseDelete ──────────────────────────────────────────────────────

    public function test_responseDelete_returns_200_with_correct_message()
    {
        $response = responseDelete();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Delete Data Successful', $response->getData()->message);
    }

    // ─── responseInvalid ─────────────────────────────────────────────────────

    public function test_responseInvalid_returns_422_with_errors()
    {
        $errors = ['Field is required', 'Email not valid'];
        $response = responseInvalid($errors);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('The given data was invalid.', $response->getData()->errors);
        $this->assertEquals($errors, (array) $response->getData()->message);
    }

    // ─── responseSuccess ─────────────────────────────────────────────────────

    public function test_responseSuccess_returns_200_with_data_and_default_message()
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = responseSuccess($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Insert Data Successful!', $response->getData()->message);
        $this->assertEquals(1, $response->getData()->data->id);
    }

    public function test_responseSuccess_returns_custom_message()
    {
        $response = responseSuccess([], 'Booking berhasil dibuat!');

        $this->assertEquals('Booking berhasil dibuat!', $response->getData()->message);
    }

    // ─── responseError ───────────────────────────────────────────────────────

    public function test_responseError_returns_500()
    {
        $response = responseError('Something went wrong');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    // ─── responseUnauthorize ─────────────────────────────────────────────────

    public function test_responseUnauthorize_returns_403()
    {
        $response = responseUnauthorize();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_responseUnauthorize_contains_default_error_message()
    {
        $response = responseUnauthorize();
        $errors   = (array) $response->getData()->errors;

        $this->assertContains('User Access not Authorize!', $errors);
    }

    // ─── responseIndex ───────────────────────────────────────────────────────

    public function test_responseIndex_returns_correct_structure()
    {
        $data     = [['id' => 1], ['id' => 2]];
        $response = responseIndex(3, $data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $response->getData()->totalPagination);
        $this->assertCount(2, $response->getData()->data);
    }

    // ─── responseList ────────────────────────────────────────────────────────

    public function test_responseList_returns_data_as_json()
    {
        $data     = [['id' => 1, 'name' => 'Milo'], ['id' => 2, 'name' => 'Biscuit']];
        $response = responseList($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $response->getData());
    }

    // ─── responseErrorValidation ─────────────────────────────────────────────

    public function test_responseErrorValidation_returns_422()
    {
        $response = responseErrorValidation(['name' => ['Nama wajib diisi']]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('The given data was invalid.', $response->getData()->message);
    }
}
