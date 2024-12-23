<?php

namespace App\Http\Controllers\Authentication;

use App\Events\Autenticator\TokenCreated;
use App\Http\Controllers\Controller;
use App\Http\Middleware\Autenticator;
use App\Http\Requests\Authentication\ResetRequest;
use App\Http\Requests\Authentication\SendRequest;
use App\Models\Authentication\Tokens;
use App\Models\User;
use App\Repositories\Authentication\LoginRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public $repository;

    public function __construct(LoginRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return view('pages.authentication.index');
    }

    public function store(Request $request)
    {

        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {

            if (Auth::user()->status == 1) {
                return redirect()->route('home.index');
            } else {
                Auth::logout();
                return back()->with("toast_error", "verifique se o email e senha foram digitados corretamente.")->withInput();
            }
        }

        return back()->with("toast_error", "verifique se o email e senha foram digitados corretamente.")->withInput();
    }

    public function reset()
    {
        return view('pages.authentication.reset');
    }

    public function edit(Tokens $token)
    {
        if (isset($token) && $token->status == 1) {
            return view('pages.authentication.edit')->with('token_id', $token->id);
        }

        return to_route('login');
    }

    public function send(SendRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            try {

                $data = $this->repository->createToken($user, "reset_password");
                
                TokenCreated::dispatch(
                    $data['name'],
                    $data['email'],
                    $data['time'],
                    $data['token'],
                    $data['title'],
                );

                return redirect()->back()->with("toast_success", "Verifique a caixa de entrada do seu email.");
            } catch (\Throwable $th) {
                return redirect()->back()->with("toast_error", "Erro ao enviar o email, tente novamente em alguns instantes.")->withInput();
            }
        }

        return redirect()->back()->with("toast_warning", "Erro ao enviar o email. Verifique se o email digitado está correto.")->withInput();
    }

    public function update(ResetRequest $request, Tokens $token)
    {
        if (isset($token) && $token->status == 1) {
            try {
                $this->repository->changePassword($request, $token);

                return to_route('login')->with("toast_success", "Senha atualizada, tente fazer login.");
            } catch (\Throwable $th) {
                return redirect()->back()->with("toast_error", "Erro, tente novamente em alguns instantes.")->withInput();
            }
        }

        return redirect()->back()->with("toast_error", "Error ao tentar alterar senha, tente novamente em alguns instantes.")->withInput();
    }

    public function register(Tokens $token)
    {
        if (isset($token) && $token->status == 1) {
            return view('pages.authentication.register')->with('token_id', $token->id);
        }

        return to_route('login');
    }
}
