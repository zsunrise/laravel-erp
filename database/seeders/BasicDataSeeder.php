<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Currency;
use App\Models\Region;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class BasicDataSeeder extends Seeder
{
    public function run()
    {
        // 创建计量单位
        $units = [
            ['name' => '个', 'code' => 'PCS', 'symbol' => '个', 'sort' => 1, 'is_active' => true],
            ['name' => '箱', 'code' => 'BOX', 'symbol' => '箱', 'sort' => 2, 'is_active' => true],
            ['name' => '包', 'code' => 'PKG', 'symbol' => '包', 'sort' => 3, 'is_active' => true],
            ['name' => '公斤', 'code' => 'KG', 'symbol' => 'kg', 'sort' => 4, 'is_active' => true],
            ['name' => '吨', 'code' => 'TON', 'symbol' => 't', 'sort' => 5, 'is_active' => true],
            ['name' => '米', 'code' => 'M', 'symbol' => 'm', 'sort' => 6, 'is_active' => true],
            ['name' => '升', 'code' => 'L', 'symbol' => 'L', 'sort' => 7, 'is_active' => true],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }

        // 创建币种
        $currencies = [
            ['name' => '人民币', 'code' => 'CNY', 'symbol' => '¥', 'exchange_rate' => 1, 'is_default' => true, 'is_active' => true],
            ['name' => '美元', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 7.2, 'is_default' => false, 'is_active' => true],
            ['name' => '欧元', 'code' => 'EUR', 'symbol' => '€', 'exchange_rate' => 7.8, 'is_default' => false, 'is_active' => true],
            ['name' => '日元', 'code' => 'JPY', 'symbol' => '¥', 'exchange_rate' => 0.05, 'is_default' => false, 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        // 创建地区（示例：中国省份）
        $provinces = [
            ['name' => '北京市', 'code' => '110000', 'level' => 1, 'sort' => 1],
            ['name' => '天津市', 'code' => '120000', 'level' => 1, 'sort' => 2],
            ['name' => '河北省', 'code' => '130000', 'level' => 1, 'sort' => 3],
            ['name' => '山西省', 'code' => '140000', 'level' => 1, 'sort' => 4],
            ['name' => '内蒙古自治区', 'code' => '150000', 'level' => 1, 'sort' => 5],
            ['name' => '辽宁省', 'code' => '210000', 'level' => 1, 'sort' => 6],
            ['name' => '吉林省', 'code' => '220000', 'level' => 1, 'sort' => 7],
            ['name' => '黑龙江省', 'code' => '230000', 'level' => 1, 'sort' => 8],
            ['name' => '上海市', 'code' => '310000', 'level' => 1, 'sort' => 9],
            ['name' => '江苏省', 'code' => '320000', 'level' => 1, 'sort' => 10],
            ['name' => '浙江省', 'code' => '330000', 'level' => 1, 'sort' => 11],
            ['name' => '安徽省', 'code' => '340000', 'level' => 1, 'sort' => 12],
            ['name' => '福建省', 'code' => '350000', 'level' => 1, 'sort' => 13],
            ['name' => '江西省', 'code' => '360000', 'level' => 1, 'sort' => 14],
            ['name' => '山东省', 'code' => '370000', 'level' => 1, 'sort' => 15],
            ['name' => '河南省', 'code' => '410000', 'level' => 1, 'sort' => 16],
            ['name' => '湖北省', 'code' => '420000', 'level' => 1, 'sort' => 17],
            ['name' => '湖南省', 'code' => '430000', 'level' => 1, 'sort' => 18],
            ['name' => '广东省', 'code' => '440000', 'level' => 1, 'sort' => 19],
            ['name' => '广西壮族自治区', 'code' => '450000', 'level' => 1, 'sort' => 20],
            ['name' => '海南省', 'code' => '460000', 'level' => 1, 'sort' => 21],
            ['name' => '重庆市', 'code' => '500000', 'level' => 1, 'sort' => 22],
            ['name' => '四川省', 'code' => '510000', 'level' => 1, 'sort' => 23],
            ['name' => '贵州省', 'code' => '520000', 'level' => 1, 'sort' => 24],
            ['name' => '云南省', 'code' => '530000', 'level' => 1, 'sort' => 25],
            ['name' => '西藏自治区', 'code' => '540000', 'level' => 1, 'sort' => 26],
            ['name' => '陕西省', 'code' => '610000', 'level' => 1, 'sort' => 27],
            ['name' => '甘肃省', 'code' => '620000', 'level' => 1, 'sort' => 28],
            ['name' => '青海省', 'code' => '630000', 'level' => 1, 'sort' => 29],
            ['name' => '宁夏回族自治区', 'code' => '640000', 'level' => 1, 'sort' => 30],
            ['name' => '新疆维吾尔自治区', 'code' => '650000', 'level' => 1, 'sort' => 31],
        ];

        foreach ($provinces as $province) {
            Region::firstOrCreate(
                ['code' => $province['code']],
                $province
            );
        }

        // 创建默认仓库
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'WH001'],
            [
                'name' => '主仓库',
                'address' => '默认地址',
                'is_default' => true,
                'is_active' => true,
                'description' => '系统默认仓库',
            ]
        );
    }
}
