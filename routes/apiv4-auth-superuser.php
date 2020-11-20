<?php

/*
 * Copyright (C) 2009 - 2019 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GpNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api/v4" middleware group. Enjoy building your API!
|
*/

// API key can be passed in the header (preferred) or on the URL.
//
//     curl -X GET -H "X-IXP-Manager-API-Key: mySuperSecretApiKey" http://ixpv.dev/api/v4/test
//     wget http://ixpv.dev/api/v4/test?apikey=mySuperSecretApiKey




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Customers
//
Route::group( [  'prefix' => 'customer' ], function() {
    Route::get( 'query-peeringdb/asn/{asn}',   'CustomerController@queryPeeringDbWithAsn' );
    Route::post( '{cust}/switches',               'CustomerController@switches' );
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Patch Panel Port
//
Route::group( [  'prefix' => 'patch-panel-port' ], function() {
    Route::group( [  'namespace' => 'PatchPanel\Port' ], function() {
        Route::delete(  'file/delete/{file}',           'FileController@delete'           );
        Route::delete(  'file/delete-history/{file}',   'FileController@deleteHistory'    );
        Route::post(  'file/toggle-privacy/{file}',     'FileController@togglePrivacy'    )->name( 'patch-panel-port-file@toogle-privacy' );
    });
});


Route::post(  'patch-panel-port/delete/{id}',                    'PatchPanelPortController@delete' );
Route::post(  'patch-panel-port/split/{id}',                     'PatchPanelPortController@split' );

Route::post(  'patch-panel-port/upload-file/{id}',               'PatchPanelPortController@uploadFile' );
Route::post(  'patch-panel-port/notes/{ppp}',                    'PatchPanelPortController@setNotes' );

Route::get(  'patch-panel-port/{ppp}',                           'PatchPanelPortController@detail');
Route::get(  'patch-panel-port/deep/{ppp}',                      'PatchPanelPortController@detailDeep');

Route::post( 'patch-panel/{pp}/free-port',                      'PatchPanelController@freePort');

Route::get('provisioner/layer2interfaces/switch/{switchid}.{outformat}',        'Provisioner\YamlController@forSwitch');
Route::get('provisioner/layer2interfaces/switch-name/{switchname}.{outformat}', 'Provisioner\YamlController@forSwitchByName')->where(['switchname' => '[A-Za-z0-9\.\-]+']);

Route::get('provisioner/layer3interfaces/switch-id/{switchid}.{outformat}',     'Provisioner\YamlController@coreLinkForSwitch');
Route::get('provisioner/layer3interfaces/switch-name/{switchname}.{outformat}', 'Provisioner\YamlController@coreLinkForSwitchByName')->where(['switchname' => '[A-Za-z0-9\.\-]+']);

Route::get('provisioner/vlans/switch-id/{switchid}.{outformat}',                'Provisioner\YamlController@vlansForSwitch');
Route::get('provisioner/vlans/switch-name/{switchname}.{outformat}',            'Provisioner\YamlController@vlansForSwitchByName')->where(['switchname' => '[A-Za-z0-9\.\-]+']);

Route::get('provisioner/routing/switch-id/{switchid}.{outformat}',              'Provisioner\YamlController@bgpForSwitch');
Route::get('provisioner/routing/switch-name/{switchname}.{outformat}',          'Provisioner\YamlController@bgpForSwitchByName')->where(['switchname' => '[A-Za-z0-9\.\-]+']);

Route::get( 'provisioner/switch/list.{outformat}',                              'Provisioner\YamlController@listSwitch');
Route::get( 'provisioner/switch/switch-id/{switchid}.{outformat}',              'Provisioner\YamlController@showSwitch');
Route::get( 'provisioner/switch/switch-name/{switchname}.{outformat}',          'Provisioner\YamlController@showSwitchByName')->where(['switchname' => '[A-Za-z0-9\.\-]+']);

Route::get( 'provisioner/corebundle/list.{outformat}',                          'Provisioner\YamlController@listCoreBundle');

Route::group( [  'prefix' => 'switch-port' ], function() {
    Route::get('{sp}/customer',                         'SwitchPortController@customer' );
    Route::get('{sp}/physical-interface',               'SwitchPortController@physicalInterface' );
});

Route::group( [  'prefix' => 'switch' ], function() {
    Route::post( '{id}/ports',                       'SwitchController@ports' );
    Route::get( '{id}/status',                       'SwitchController@status' );
    Route::get( '{id}/core-bundles-status',          'SwitchController@coreBundlesStatus' );
    Route::post( '{s}/switch-port-for-ppp',          'SwitchController@switchPortForPPP' )->name( 'switch@switch-port-for-ppp' );
    Route::post( '{s}/switch-port-prewired',         'SwitchController@switchPortPrewired' )->name( 'switch@switch-port-prewired' );
});


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Layer 2 Address
//
//Route::get(  'l2-address/detail/{l2a}', 'Layer2AddressController@detail' );




Route::group( [  'prefix' => 'vlan' ], function() {
    Route::get( '{v}/ip-addresses',                    'VlanController@ipAddresses' );
    Route::post( 'ip-address/used-across-vlans',        'VlanController@UsedAcrossVlans' )->name( 'vlan@used-across-vlans' );
});

Route::group( [ 'namespace' => 'Customer\Note', 'prefix' => 'customer-note' ], function() {
    Route::get(    'notify-toggle/customer/{id}', 'CustomerNotesController@notifyToggleCustomer' )->name( 'customer-notes@notify-toggle-customer');
    Route::get(    'notify-toggle/note/{id}',     'CustomerNotesController@notifyToggleNote'     )->name( 'customer-notes@notify-toggle-note');

    Route::post(    'add',                             'CustomerNotesController@add'                    )->name( 'customer-notes@add');
    Route::post(    'delete/{id}',                     'CustomerNotesController@delete'                 )->name( 'customer-notes@delete');
});

