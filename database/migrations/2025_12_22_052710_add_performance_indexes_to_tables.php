<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 为 customers 表添加搜索索引
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'customers_name_index')) {
                $table->index('name', 'customers_name_index');
            }
            if (!$this->indexExists('customers', 'customers_is_active_index')) {
                $table->index('is_active', 'customers_is_active_index');
            }
        });

        // 为 suppliers 表添加搜索索引
        Schema::table('suppliers', function (Blueprint $table) {
            if (!$this->indexExists('suppliers', 'suppliers_name_index')) {
                $table->index('name', 'suppliers_name_index');
            }
            if (!$this->indexExists('suppliers', 'suppliers_is_active_index')) {
                $table->index('is_active', 'suppliers_is_active_index');
            }
        });

        // 为 products 表添加搜索索引
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_name_index')) {
                $table->index('name', 'products_name_index');
            }
            if (!$this->indexExists('products', 'products_is_active_index')) {
                $table->index('is_active', 'products_is_active_index');
            }
            if (!$this->indexExists('products', 'products_category_active_index')) {
                $table->index(['category_id', 'is_active'], 'products_category_active_index');
            }
        });

        // 为 users 表添加搜索索引
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
            if (!$this->indexExists('users', 'users_name_index')) {
                $table->index('name', 'users_name_index');
            }
            if (!$this->indexExists('users', 'users_is_active_index')) {
                $table->index('is_active', 'users_is_active_index');
            }
        });

        // 为 sales_orders 表添加搜索索引
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!$this->indexExists('sales_orders', 'sales_orders_order_no_index')) {
                $table->index('order_no', 'sales_orders_order_no_index');
            }
            if (!$this->indexExists('sales_orders', 'sales_orders_order_date_index')) {
                $table->index('order_date', 'sales_orders_order_date_index');
            }
            if (!$this->indexExists('sales_orders', 'sales_orders_customer_status_index')) {
                $table->index(['customer_id', 'status'], 'sales_orders_customer_status_index');
            }
        });

        // 为 purchase_orders 表添加搜索索引
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!$this->indexExists('purchase_orders', 'purchase_orders_order_no_index')) {
                $table->index('order_no', 'purchase_orders_order_no_index');
            }
            if (!$this->indexExists('purchase_orders', 'purchase_orders_order_date_index')) {
                $table->index('order_date', 'purchase_orders_order_date_index');
            }
            if (!$this->indexExists('purchase_orders', 'purchase_orders_supplier_status_index')) {
                $table->index(['supplier_id', 'status'], 'purchase_orders_supplier_status_index');
            }
        });

        // 为 operation_logs 表添加搜索索引
        if (Schema::hasTable('operation_logs')) {
            Schema::table('operation_logs', function (Blueprint $table) {
                if (!$this->indexExists('operation_logs', 'operation_logs_user_id_index')) {
                    $table->index('user_id', 'operation_logs_user_id_index');
                }
                if (!$this->indexExists('operation_logs', 'operation_logs_module_index')) {
                    $table->index('module', 'operation_logs_module_index');
                }
                if (!$this->indexExists('operation_logs', 'operation_logs_created_at_index')) {
                    $table->index('created_at', 'operation_logs_created_at_index');
                }
            });
        }

        // 为 boms 表添加搜索索引
        if (Schema::hasTable('boms')) {
            Schema::table('boms', function (Blueprint $table) {
                if (!$this->indexExists('boms', 'boms_version_index')) {
                    $table->index('version', 'boms_version_index');
                }
                if (!$this->indexExists('boms', 'boms_product_active_index')) {
                    $table->index(['product_id', 'is_active'], 'boms_product_active_index');
                }
            });
        }

        // 为 process_routes 表添加搜索索引
        if (Schema::hasTable('process_routes')) {
            Schema::table('process_routes', function (Blueprint $table) {
                if (!$this->indexExists('process_routes', 'process_routes_version_index')) {
                    $table->index('version', 'process_routes_version_index');
                }
                if (!$this->indexExists('process_routes', 'process_routes_product_active_index')) {
                    $table->index(['product_id', 'is_active'], 'process_routes_product_active_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_name_index');
            $table->dropIndex('customers_is_active_index');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_name_index');
            $table->dropIndex('suppliers_is_active_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_name_index');
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_category_active_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_name_index');
            $table->dropIndex('users_is_active_index');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('sales_orders_order_no_index');
            $table->dropIndex('sales_orders_order_date_index');
            $table->dropIndex('sales_orders_customer_status_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_order_no_index');
            $table->dropIndex('purchase_orders_order_date_index');
            $table->dropIndex('purchase_orders_supplier_status_index');
        });

        if (Schema::hasTable('operation_logs')) {
            Schema::table('operation_logs', function (Blueprint $table) {
                $table->dropIndex('operation_logs_user_id_index');
                $table->dropIndex('operation_logs_module_index');
                $table->dropIndex('operation_logs_created_at_index');
            });
        }

        if (Schema::hasTable('boms')) {
            Schema::table('boms', function (Blueprint $table) {
                $table->dropIndex('boms_version_index');
                $table->dropIndex('boms_product_active_index');
            });
        }

        if (Schema::hasTable('process_routes')) {
            Schema::table('process_routes', function (Blueprint $table) {
                $table->dropIndex('process_routes_version_index');
                $table->dropIndex('process_routes_product_active_index');
            });
        }
    }

    /**
     * 检查索引是否存在
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $indexes = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );
        return $indexes[0]->count > 0;
    }
};
