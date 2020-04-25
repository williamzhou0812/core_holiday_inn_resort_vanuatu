<?php

namespace TCG\Voyager;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Intervention\Image\ImageServiceProvider;
use Larapack\DoctrineSupport\DoctrineSupportServiceProvider;
use Larapack\VoyagerHooks\VoyagerHooksServiceProvider;
use TCG\Voyager\Events\FormFieldsRegistered;
use TCG\Voyager\Facades\Voyager as VoyagerFacade;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\FormFields\After\DescriptionHandler;
use TCG\Voyager\Http\Middleware\VoyagerAdminMiddleware;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Setting;
use TCG\Voyager\Policies\BasePolicy;
use TCG\Voyager\Policies\MenuItemPolicy;
use TCG\Voyager\Policies\SettingPolicy;
use TCG\Voyager\Providers\VoyagerDummyServiceProvider;
use TCG\Voyager\Providers\VoyagerEventServiceProvider;
use TCG\Voyager\Translator\Collection as TranslatorCollection;
use TCG\Voyager\FormFields\SubSectionHandler;
use TCG\Voyager\FormFields\VisibleHandler;
use TCG\Voyager\FormFields\DateTimeHandler;

class TheCoreServiceProvider extends VoyagerServiceProvider
{
    public function register() {
        parent::register();
        Voyager::addFormField(VisibleHandler::class);
        Voyager::addFormField(DateTimeHandler::class);
    }
}