@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('All Notes') }}</h1>
            </div>
            @can('add_note')
                <div class="col-md-6 text-md-right">
                    <a href="{{ route('note.create') }}" class="btn btn-circle btn-info">
                        <span>{{ translate('Add New Note') }}</span>
                    </a>
                </div>
            @endcan
        </div>
    </div>
    <div class="card">
        <div class="card-header d-block d-md-flex">
            <h5 class="mb-0 h6">{{ translate('notes') }}</h5>
            <form class="" id="sort_notes" action="" method="GET">
                <div class="box-inline pad-rgt pull-left">
                    <div class="" style="min-width: 200px;">
                        <input type="text" class="form-control" id="search" name="search"
                            @isset($sort_search)
                        value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type name & Enter') }}">
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th data-breakpoints="lg">#</th>
                        <th data-breakpoints="lg">{{ translate('Type') }}</th>
                        <th data-breakpoints="lg">{{ translate('Description') }}</th>

                        <th width="10%" class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notes as $key => $note)
                        <tr>
                            <td>{{ $key + 1 + ($notes->currentPage() - 1) * $notes->perPage() }}</td>
                            <td>{{ translate($note->note_type) }}</td>
                            <td>
                                <p class="text-truncate-2">{{ $note->getTranslation('description') }}</p>
                            </td>
                            <td class="text-right">
                                @can('edit_note')
                                    <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                        href="{{route('note.edit', ['id'=>$note->id, 'lang'=>env('DEFAULT_LANGUAGE')] )}}"
                                        title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                                @can('delete_note')
                                    <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                        data-href="{{ route('note.delete', $note->id) }}" title="{{ translate('Delete') }}">
                                        <i class="las la-trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $notes->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection


@section('modal')
    @include('modals.delete_modal')
@endsection


@section('script')
    <script type="text/javascript"></script>
@endsection
