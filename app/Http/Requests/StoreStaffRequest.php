<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use DB;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return checkAccessModify('staff-list', $this->user()->roleId);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'locationId_array' => json_decode($this->locationId, true) ?? [],
            'detailAddress_array' => json_decode($this->detailAddress, true) ?? [],
            'telephone_array' => json_decode($this->telephone, true) ?? [],
            'email_array' => json_decode($this->email, true) ?? [],
            'messenger_array' => json_decode($this->messenger, true) ?? [],
            'typeIdentifications_array' => json_decode($this->typeIdentifications, true) ?? [],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'firstName' => 'required|max:20|min:3',
            'middleName' => 'max:20|min:3|nullable',
            'lastName' => 'max:20|min:3|nullable',
            'nickName' => 'max:20|min:3|nullable',
            'gender' => 'string|nullable',
            'status' => 'required|integer',
            'lineManagerId' => 'required|integer',
            'jobTitleId' => 'required|integer',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'registrationNo' => 'string|max:20|min:5|nullable',
            'designation' => 'string|max:20|min:5|nullable',
            'locationId' => 'required',
            'annualSickAllowance' => 'integer|nullable',
            'annualLeaveAllowance' => 'integer|nullable',
            'payPeriodId' => 'required|integer',
            'payAmount' => 'numeric|nullable',
            'additionalInfo' => 'string|nullable|max:100',
            'generalCustomerCanSchedule' => 'integer|nullable',
            'generalCustomerReceiveDailyEmail' => 'integer|nullable',
            'generalAllowMemberToLogUsingEmail' => 'integer|nullable',
            'reminderEmail' => 'integer|nullable',
            'reminderWhatsapp' => 'integer|nullable',
            'roleId' => 'integer|nullable',

            'detailAddress_array' => 'required|array|min:1',
            'detailAddress_array.*.addressName' => 'required',
            'detailAddress_array.*.provinceCode' => 'required',
            'detailAddress_array.*.cityCode' => 'required',
            'detailAddress_array.*.country' => 'required',

            'telephone_array' => 'nullable|array',
            'telephone_array.*.phoneNumber' => 'required',
            'telephone_array.*.type' => 'required',
            'telephone_array.*.usage' => 'required',

            'email_array' => 'required|array|min:1',
            'email_array.*.email' => 'required|email',
            'email_array.*.usage' => 'required',

            'messenger_array' => 'nullable|array',
            'messenger_array.*.messengerNumber' => 'required',
            'messenger_array.*.type' => 'required',
            'messenger_array.*.usage' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'detailAddress_array.*.addressName.required' => 'Address name on tab Address is required',
            'detailAddress_array.*.provinceCode.required' => 'Province code on tab Address is required',
            'detailAddress_array.*.cityCode.required' => 'City code on tab Address is required',
            'detailAddress_array.*.country.required' => 'Country on tab Address is required',
            
            'telephone_array.*.phoneNumber.required' => 'Phone Number on tab telephone is required',
            'telephone_array.*.type.required' => 'Type on tab telephone is required',
            'telephone_array.*.usage.required' => 'Usage on tab telephone is required',

            'email_array.*.email.required' => 'Email on tab email is required',
            'email_array.*.usage.required' => 'Usage on tab email is required',

            'messenger_array.*.messengerNumber.required' => 'Messenger number on tab messenger is required',
            'messenger_array.*.type.required' => 'Type on tab messenger is required',
            'messenger_array.*.usage.required' => 'Usage on tab messenger is required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            
            // Custom Address Validation
            $detailAddress = $this->detailAddress_array;
            $primaryCount = collect($detailAddress)->where('isPrimary', 1)->count();
            if ($primaryCount == 0) {
                $validator->errors()->add('detailAddress', 'Detail address must have at least 1 primary address');
            } elseif ($primaryCount > 1) {
                $validator->errors()->add('detailAddress', 'Detail address have 2 primary address, please check again');
            }

            // Custom Telephone Validation
            $telephones = $this->telephone_array;
            $primaryPhoneCount = collect($telephones)->filter(function ($item) {
                return strtolower($item['usage']) == "utama" || strtolower($item['usage']) == "primary";
            })->count();
            if ($primaryPhoneCount > 1) {
                $validator->errors()->add('telephone', 'Usage utama on phone must only one!');
            }
            foreach ($telephones as $phone) {
                if (strtolower($phone['type']) == "whatshapp" && substr($phone['phoneNumber'], 0, 2) !== "62") {
                    $validator->errors()->add('telephone', 'Please check your phone number, for type whatshapp must start with 62');
                }
                $exists = DB::table('usersTelephones')
                    ->where('phoneNumber', $phone['phoneNumber'])
                    ->where('isDeleted', 0)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('telephone', 'Phonenumber : ' . $phone['phoneNumber'] . ' already exists, please try different number');
                }
            }

            // Custom Email Validation
            $emails = $this->email_array;
            $primaryEmailCount = collect($emails)->filter(function ($item) {
                return strtolower($item['usage']) == "utama" || strtolower($item['usage']) == "primary";
            })->count();
            if ($primaryEmailCount > 1) {
                $validator->errors()->add('email', 'Usage utama on email must only one!');
            }
            if ($primaryEmailCount == 0) {
                $validator->errors()->add('email', 'Must have one primary email');
            }
            foreach ($emails as $email) {
                $exists = DB::table('usersEmails')
                    ->where('email', $email['email'])
                    ->where('isDeleted', 0)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('email', 'Email : ' . $email['email'] . ' already exists, please try different email address');
                }
            }

            // Custom Messenger Validation
            $messengers = $this->messenger_array;
            $primaryMessengerCount = collect($messengers)->filter(function ($item) {
                return strtolower($item['usage']) == "utama" || strtolower($item['usage']) == "primary";
            })->count();
            if ($primaryMessengerCount > 1) {
                $validator->errors()->add('messenger', 'Usage utama on messenger must only one!');
            }
            foreach ($messengers as $msg) {
                if (strtolower($msg['type']) == "whatshapp" && substr($msg['messengerNumber'], 0, 2) !== "62") {
                    $validator->errors()->add('messenger', 'Please check your messenger number, for type whatshapp must start with 62');
                }
                $exists = DB::table('usersMessengers')
                    ->where('messengerNumber', $msg['messengerNumber'])
                    ->where('isDeleted', 0)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('messenger', 'Messenger number : ' . $msg['messengerNumber'] . ' already exists, please try different number');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()->all(),
        ], 422));
    }
}
