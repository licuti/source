<?php

namespace App\Controllers\Admin;

use App\Core\Request;use App\Models\LocationModel;

class LocationController extends BaseAdminController
{
    public function getProvincesAjax(Request $request)
    {
        $countryId = $request->input('country_id', 0);
        if ($countryId) {
            $provinces = LocationModel::where('type', 'province')->where('parent_id', $countryId)->orderBy('name', 'ASC')->get();
            return $this->json(array_values((array)$provinces));
        }
        return $this->json([]);
    }

    public function getDistrictsAjax(Request $request)
    {
        $provinceId = $request->input('province_id', 0);
        if ($provinceId) {
            $districts = LocationModel::where('type', 'district')->where('parent_id', $provinceId)->orderBy('name', 'ASC')->get();
            return $this->json(array_values((array)$districts));
        }
        return $this->json([]);
    }

    public function getWardsAjax(Request $request)
    {
        $districtId = $request->input('district_id', 0);
        if ($districtId) {
            $wards = LocationModel::where('type', 'ward')->where('parent_id', $districtId)->orderBy('name', 'ASC')->get();
            return $this->json(array_values((array)$wards));
        }
        return $this->json([]);
    }
}
