<tr data-cursor-item="admin-category-{{ $item->id }}">
    <td>{{ $item->id }}</td>
    <td>{{ $item->name }}</td>
    <td>
        <div class="d-flex gap-2 flex-wrap">
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalEditCategory"
                data-id="{{ $item->id }}"
                data-name="{{ $item->name }}"
            >
                Edit
            </button>
            <button
                type="button"
                class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteCategory"
                data-id="{{ $item->id }}"
                data-name="{{ $item->name }}"
            >
                Delete
            </button>
        </div>
    </td>
</tr>
