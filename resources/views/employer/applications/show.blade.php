@extends('layouts.portal')

@php use App\Support\Phone; @endphp

@section('title', $application->user->name)
@section('heading', $application->user->name)

@section('content')

    <div class="adm-detail">

        <div>
            <div class="adm-block">
                <h5>{{ __('employer.applications.applicant') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('Name') }}</dt>
                        <dd>{{ $application->user->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Mobile number') }}</dt>
                        <dd dir="ltr">
                            <a href="tel:{{ $application->user->phone }}">{{ Phone::format($application->user->phone) }}</a>
                        </dd>
                    </div>
                    @if ($application->user->email)
                        <div>
                            <dt>{{ __('contactDialog.email') }}</dt>
                            <dd dir="ltr" style="word-break:break-all">
                                <a href="mailto:{{ $application->user->email }}">{{ $application->user->email }}</a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('employer.applications.received') }}</dt>
                        <dd>{{ $application->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($application->cover_letter)
                <div class="adm-block">
                    <h5>
                        {{ __('employer.applications.coverLetter') }}
                        @if ($application->cover_letter_ai_generated)
                            <span class="tag tag-neutral" style="margin-inline-start:var(--space-3)">
                                {{ __('employer.applications.aiGenerated') }}
                            </span>
                        @endif
                    </h5>

                    <p style="margin:0;white-space:pre-line;line-height:1.8">{{ $application->cover_letter }}</p>
                </div>
            @endif

            <div class="adm-block">
                <h5>{{ __('employer.applications.appliedFor') }}</h5>

                <p style="margin:0">
                    <a href="{{ lroute('employer.jobs.edit', $application->job) }}" class="adm-cell-title">
                        {{ $application->job->title }}
                    </a>
                    <span class="adm-cell-sub">
                        {{ $application->job->city->name }} · {{ $application->job->category->name }}
                    </span>
                </p>
            </div>
        </div>

        <aside>
            <div class="adm-block">
                <h5>{{ __('employer.applications.cv') }}</h5>

                @if ($application->cv_path)
                    {{--
                        CVs are on the private disk, not in public/. A CV holds
                        a name, a phone number and an address; a guessable
                        public URL for one is a data leak waiting to happen.
                        Served through a signed, auth-checked route when that is
                        built — for now the path is recorded and shown.
                    --}}
                    <p class="text-muted" style="margin:0;font-size:12.5px;word-break:break-all">
                        {{ $application->cv_path }}
                    </p>
                @else
                    <p class="text-muted" style="margin:0;font-size:13px">{{ __('employer.applications.noCv') }}</p>
                @endif
            </div>

            <div class="adm-block">
                <h5>{{ __('employer.applications.setStatus') }}</h5>

                <form method="POST" action="{{ lroute('employer.applications.status', $application) }}">
                    @csrf @method('PATCH')

                    <div class="field">
                        <label for="status">{{ __('admin.status.all') }}</label>
                        <select id="status" class="input" name="status">
                            @foreach (['viewed', 'shortlisted', 'rejected', 'hired'] as $s)
                                <option value="{{ $s }}" @selected($application->status === $s)>
                                    {{ __('employer.applications.statuses.'.$s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field" style="margin-top:var(--space-4)">
                        <label for="employer_note">{{ __('employer.applications.note') }}</label>
                        <textarea id="employer_note" class="input" name="employer_note" rows="3">{{ $application->employer_note }}</textarea>
                        <span class="text-muted" style="font-size:12px">{{ __('employer.applications.noteHint') }}</span>
                    </div>

                    <button type="submit" class="btn-blue btn-wide" style="margin-top:var(--space-4)">
                        {{ __('employer.company.save') }}
                    </button>
                </form>
            </div>
        </aside>
    </div>

@endsection
