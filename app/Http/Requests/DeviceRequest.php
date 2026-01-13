<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'device_names' => 'required|min:5|max:100',
            'mac_devices' => 'required',
            'tipe_device' => 'required|min:5|max:6',
            'status' => 'required|boolean',
            'rssi' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'device_names.required' => 'Butuh Nama Device',
            'mac_devices.required' => 'Butuh Mac Address',
            'tipe_device.required' => 'Butuh Tipe Device',
            'device_names.required.min' => '',
            'device_names.required.max' => '',
            'tipe_device.required.min' => '',
            'tipe_device.required.max' => '',
        ];
    }
}
