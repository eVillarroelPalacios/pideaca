<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContactInquiryController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $status = (string) $request->query('status', '');

        $query = ContactInquiry::orderByDesc('created_at')->orderByDesc('id');

        if (in_array($status, ['new', 'in_progress', 'resolved'], true)) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

    public function updateStatus(Request $request, ContactInquiry $contactInquiry)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'resolved'])],
        ], [
            'status.required' => 'Seleccioná un estado.',
            'status.in' => 'Seleccioná un estado válido.',
        ]);

        $contactInquiry->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'contact_inquiry' => $contactInquiry->fresh(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'inquiry_type' => Str::lower(trim((string) $request->input('inquiry_type'))),
            'message' => trim((string) $request->input('message')),
        ]);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'inquiry_type' => ['required', 'string', Rule::in(['consulta', 'sugerencia', 'reclamo'])],
            'message' => 'required|string|max:5000',
        ], [
            'full_name.required' => 'Ingresá tu nombre completo.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'inquiry_type.required' => 'Seleccioná el tipo de consulta.',
            'inquiry_type.in' => 'Seleccioná un tipo de consulta válido.',
            'message.required' => 'Escribí tu mensaje.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
        ]);

        ContactInquiry::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'inquiry_type' => $data['inquiry_type'],
            'message' => $data['message'],
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Gracias! Recibimos tu mensaje y te responderemos a la brevedad.',
        ], 201);
    }
}
