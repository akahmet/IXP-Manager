<?php

namespace IXP\Http\Controllers;

/*
 * Copyright (C) 2009-2017 Internet Neutral Exchange Association Company Limited By Guarantee.
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
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Auth, D2EM, Log, Route;

use Entities\{
    User as UserEntity
};

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use Illuminate\View\View;

use Illuminate\Support\Facades\View as ViewFacade;

use IXP\Exceptions\GeneralException;
use IXP\Utils\View\Alert\Alert;
use IXP\Utils\View\Alert\Container as AlertContainer;

/**
 * Doctrine2Frontend Functions
 *
 * Based on Barry's original code from:
 *     https://github.com/opensolutions/OSS-Framework/blob/master/src/OSS/Controller/Action/Trait/Doctrine2Frontend.php
 *
 *
 * @see        http://docs.ixpmanager.org/dev/frotnend-crud/
 * @author     Barry O'Donovan <barry@islandbridgenetworks.ie>
 * @author     Yann Robin <yann@islandbridgenetworks.ie>
 * @category   Http\Controllers
 * @copyright  Copyright (C) 2009-2017 Internet Neutral Exchange Association Company Limited By Guarantee
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
abstract class Doctrine2Frontend extends Controller {

    /**
     * Parameters used by the frontend controller
     * @var object Parameters used by the frontend controller
     */
    protected $feParams = null;

    protected $data     = null;

    protected $params   = null;

    protected $view     = null;

    /**
     * The object being added / edited
     */
    protected $object = null;

    /**
     * The URL prefix to use.
     *
     * Automatically dertermined based on the crontroller name if not set.
     *
     * @var string
     */
    protected static $route_prefix = null;

    /**
     * The minimum privileges required to access this controller.
     *
     * If you set this to less than the superuser, you need to manage privileges and access
     * within your own implementation yourself.
     *
     * @var int
     */
    public static $minimum_privilege = UserEntity::AUTH_SUPERUSER;

    /**
     * Is this a read only controller?
     *
     * @var boolean
     */
    public static $read_only = false;

    /**
     * Column / table data types when displaying data.
     * @var array
     */
    static public $FE_COL_TYPES = [
        'HAS_ONE'           => 'hasOne',
        'CUSTOM_HAS_ONE'    => 'customHasOne',
        'DATETIME'          => 'datetime',
        'DATE'              => 'date',
        'TIME'              => 'time',
        'SCRIPT'            => 'script',
        'SPRINTF'           => 'sprintf',
        'REPLACE'           => 'replace',
        'XLATE'             => 'xlate',
        'YES_NO'            => 'yes_no'
    ];


    /**
     * The class's initialisation method.
     *
     */
    public function __construct( ){
        $this->feInit();
        $this->data[ 'col_types' ] = self::$FE_COL_TYPES;
    }



    /**
     * This must be overridden.
     */
    abstract protected function feInit();


    /**
     * The default routes for a Doctrine2Frontend class
     */
    public static function routes() {


        // add leading slash to class name for absolute resolution:
        $class = '\\' . get_called_class();
        $route_prefix = self::route_prefix();

        Route::group( [ 'prefix' => $route_prefix ], function() use ( $class, $route_prefix ) {

            Route::get( 'list', $class . '@list' )->name( $route_prefix . '@list' );
            Route::get( 'view/{id}', $class . '@view' )->name( $route_prefix . '@view' );

            if( !static::$read_only ) {
                Route::get(  'add',         $class . '@add'     )->name( $route_prefix . '@add'     );
                Route::post( 'delete',      $class . '@delete'  )->name( $route_prefix . '@delete'  );
                Route::get(  'edit/{id}',   $class . '@edit'    )->name( $route_prefix . '@edit'    );
                Route::post( 'store',       $class . '@store'   )->name( $route_prefix . '@store'   );
            }
        });

        $class::additionalRoutes( $route_prefix );
    }

    /**
     * Work out the route prefix
     */
    public static function route_prefix() {

        $class = get_called_class();

        if( $class::$route_prefix ) {
            return $class::$route_prefix;
        } else {
            return kebab_case( substr( class_basename( $class ), 0, -10 ) );
        }

    }

    /**
     * Function which can be over-ridden to add additional routes
     *
     * If you don't want to use the defaults as well as some additionals, override
     * `routes()` instead.
     *
     * @param string $route_prefix
     * @return void
     */
    protected static function additionalRoutes( string $route_prefix ) {}


    /**
     * Provide array of table rows for the list action (and view action)
     *
     * @param int $id The `id` of the row to load for `view` action. `null` if `list` action.
     * @return array
     */
    abstract protected function listGetData( $id = null );

    /**
     * Function which can be over-ridden to perform any pre-list tasks
     *
     * E.g. adding elements to $this->view for the pre/post-amble templates.
     *
     * @return void
     */
    protected function preList() {}


    /**
     * List the contents of a database table.
     *
     * @return View
     */
    public function list(): View
    {
        $this->data[ 'data' ]           = $this->listGetData();

        $this->view[ 'listEmptyMessage'] = $this->resolveTemplate( 'list-empty-message', false );
        $this->view[ 'listHeadOverride'] = $this->resolveTemplate( 'list-head-override', false );
        $this->view[ 'listRowOverride']  = $this->resolveTemplate( 'list-row-override',  false );
        $this->view[ 'listPreamble']     = $this->resolveTemplate( 'list-preamble',      false );
        $this->view[ 'listPostamble']    = $this->resolveTemplate( 'list-postamble',     false );
        $this->view[ 'listRowMenu']      = $this->resolveTemplate( 'list-row-menu',      false );
        $this->view[ 'listScript' ]      = $this->resolveTemplate( 'js/list' );

        $this->preList();

        return $this->display( 'list' );
    }

    /**
     * Provide single object for view.
     *
     * @param int $id The `id` of the row to load for `view` action.
     * @return array
     */
    protected function viewGetData( $id ): array {

        $data = $this->listGetData( $id );

        if( is_array( $data ) && reset( $data ) ) {
            // get first value of the array
            return $data[0];
        }

        abort( 404);
    }

    /**
     * View an object
     *
     * @param int $id The `id` of the row to load for `view` action.
     * @return View
     */
    public function view( $id ): View
    {
        $this->data[ 'data' ]               = $this->viewGetData( $id ) ;

        $this->view[ 'viewPreamble']        = $this->resolveTemplate( 'view-preamble',      false );
        $this->view[ 'viewPostamble']       = $this->resolveTemplate( 'view-postamble',     false );
        $this->view[ 'viewRowOverride']     = $this->resolveTemplate( 'view-row-override',  false );
        $this->view[ 'viewScript' ]         = $this->resolveTemplate( 'js/view',            false );

        return $this->display( 'view' );
    }


    /**
     * Prepares data for the add / edit form
     * @param int|null $id
     * @return array
     * @throws GeneralException
     */
    protected function addEditPrepareForm( $id = null ): array {
        throw new GeneralException( 'For non-read-only Doctrine2Frontend controllers, you must override this method.' );
    }

    /**
     * Common set up tasks for add and edit actions.
     */
    protected function addEditSetup()
    {
        $this->view[ 'editForm']        = $this->resolveTemplate( 'edit-form' );

        $this->view[ 'editPreamble']    = $this->resolveTemplate( 'edit-preamble',      false );
        $this->view[ 'editPostamble']   = $this->resolveTemplate( 'edit-postamble',     false );
        $this->view[ 'editScript' ]     = $this->resolveTemplate( 'js/edit',            false );

        $this->params['data'] = $this->data;
    }

    /**
     * Add an object
     */
    public function add()
    {
        $this->params = $this->addEditPrepareForm();
        $this->params['isAdd'] = true;
        $this->addEditSetup();

        return $this->display( 'edit' );
    }

    /**
     * Edit an object
     * @param int $id ID of the object to edit
     * @return view
     */
    public function edit( $id ){
        $this->params = $this->addEditPrepareForm( $id );
        $this->params['isAdd'] = false;
        $this->addEditSetup();

        return $this->display( 'edit' );
    }


    /**
     * Function to do the actual validation and storing of the submitted object.
     * @param Request $request
     * @throws GeneralException
     */
    public function doStore( Request $request ) {
        throw new GeneralException( 'For non-read-only Doctrine2Frontend controllers, you must override this method.' );
    }

    /**
     * Action for storing a new/updated object
     * @param Request $request
     * @return RedirectResponse
     */
    public function store( Request $request )
    {
        $storeResult = $this->doStore( $request );

        if( $storeResult !== true ) {
            return $storeResult;
        }

        $action = $request->input( 'id', '' )  ? "edited" : "added";
        $this->postFlush( $action );

        Log::notice( ( Auth::check() ? Auth::user()->getUsername() : 'A public user' ) . ' ' . $action
            . ' ' . $this->data['feParams']->nameSingular . ' with ID ' . $this->object->getId() );
        AlertContainer::push(  $this->feParams->titleSingular . " " . $action, Alert::SUCCESS );

        return redirect()->action( $this->feParams->defaultController . '@' . $this->feParams->defaultAction );
    }

    /**
     * Optional method to be oiverridden if a D2F controllers needs to perform post-database flush actions
     *
     * @param string $action Either 'add', 'edit', 'delete'
     * @return bool
     */
    protected function postFlush( string $action ): bool
    {
        return true;
    }



    /**
     * Function which can be over-ridden to perform any pre-deletion tasks
     *
     * You can stop the deletion by returning false but you should also add a
     * message to explain why (to the AlertContainer).
     *
     * The object to be deleted is available via `$this->>object`
     *
     * @return bool Return false to stop / cancel the deletion
     */
    protected function preDelete(): bool {
        return true;
    }

    /**
     * Delete an object
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete( Request $request ) {

        if( !( $this->object = D2EM::getRepository( $this->feParams->entity )->find( $request->input( 'id' ) ) ) ) {
            return abort( '404' );
        }

        if( $this->preDelete() ) {
            D2EM::remove( $this->object );
            D2EM::flush();
            $this->postFlush( 'delete' );
            AlertContainer::push( $this->feParams->titleSingular . " deleted.", Alert::SUCCESS );
        }

        return redirect()->action( $this->feParams->defaultController.'@'.$this->feParams->defaultAction );
    }



    /**
     * Displays the standard Frontend template or the controllers overridden version.
     *
     * @see _resolveTemplate()
     * @param string $tpl The template to display
     * @return View
     */
    protected function display( $tpl ): View {

        $this->data['feParams']->route_prefix = self::route_prefix();

        return view( $this->resolveTemplate( $tpl ) )->with( [
            'data'   => $this->data ,
            'view'   => $this->view,
            'params' => $this->params
        ]);
    }

    /**
     * Resolves the standard Frontend template or the controllers overridden version.
     *
     * All frontend actions have their own template: `frontend/{$action}.foil.php` which is
     * displayed by default. You can however override these by creating a template named:
     * `{$controller}/{$action}.foil.php`. This function looks for an overriding template
     * and displays that if it exists, otherwise it displays the default.
     *
     * This will also work for subdirectories: e.g. `$tpl = forms/add.phtml` is also valid.
     *
     * @param string $tpl The template to display
     * @param bool $quitOnMissing If a template is not found, this normally throws a 404. If this is set to false, the function returns false instead.
     * @return bool|string The template to use of false if none found
     */
    protected function resolveTemplate( $tpl, $quitOnMissing = true ) {

        if( ViewFacade::exists ( $this->feParams->viewFolderName . "/{$tpl}" ) ) {
            return $this->feParams->viewFolderName . "/{$tpl}";
        } else if( ViewFacade::exists( "frontend/{$tpl}"  ) ) {
            return "frontend/{$tpl}";
        } else if( ViewFacade::exists( $tpl  ) ) {
            return $tpl;
        }

        if( $quitOnMissing ) {
            abort( 404, "No template exists in frontend or controller's view directory for " . $tpl );
        }

        return false;
    }

}