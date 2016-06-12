<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\User;
use Auth;
use Session;
use Illuminate\Foundation\Validation\ValidatesRequests;

class UsersController extends Controller
{
    use ValidatesRequests;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\User
     */
    protected $user;

    /**
     * @param Rubenwouters\CrmLauncher\Models\User $user
     */
    public function __construct(
        User $user
    ) {
        $this->user = $user;
    }

    /**
     * Returns team overview
     *
     * @return view
     */
    public function index()
    {
        $team = $this->user->where('canViewCRM', 1)->paginate(7, ['*'], 'team');

        return view('crm-launcher::users.index')->with('team', $team);
    }

    /**
     * returns add user view
     *
     * @return view
     */
    public function addUser()
    {
        return view('crm-launcher::users.add');
    }

    /**
     * Creates new team member
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUser(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->canViewCRM = 1;
        $user->save();

        return redirect()->action('\Rubenwouters\CrmLauncher\Controllers\UsersController@index');
    }

    /**
     * Toggle users permission to view CRM
     *
     * @param  integer $id
     *
     * @return void
     */
    public function toggleUser($id)
    {
        $user = $this->user->find($id);

        if (Auth::user()->id != $id) {
            $state = $user->canViewCRM;
            $user->canViewCRM = !$state;
            $user->save();
        } else {
            Session::flash('flash_error', trans('crm-launcher::errors.de-auth_own'));
        }

        return back();
    }

    /**
     * Search user by name or e-mail
     *
     * @param  Request $request
     *
     * @return view
     */
    public function searchUser(Request $request)
    {
        $this->validate($request, [
            'keywords' => 'required|min:1',
        ]);

        $keywords = $request->input('keywords');

        $team = $this->user->where('canViewCRM', 1)->where(function($query) use ($keywords) {
            $query->where('name', 'LIKE', '%' . $keywords . '%')
                ->orWhere('email', 'LIKE', '%' . $keywords . '%');
        })->paginate(3, ['*'], 'team');

        $otherUsers = $this->user->where('canViewCRM', 0)->where(function($query) use ($keywords) {
            $query->where('name', 'LIKE', '%' . $keywords . '%')
                ->orWhere('email', 'LIKE', '%' . $keywords . '%');
        })->paginate(4, ['*'], 'users');

        return view('crm-launcher::users.index')
            ->with('team', $team)
            ->with('otherUsers', $otherUsers)
            ->with('keywords', $keywords);
    }
}
