<form action="{{ route('portfolios.destroy', $portfolio->id) }}" method="DELETE">
    @csrf
    <div class='btn-group'>
        <a href="{{ route('portfolios.show', [$portfolio->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
        <a href="{{ route('portfolios.edit', [$portfolio->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
        <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this portfolio?')"><i class="fa fa-trash"></i></button>
    </div>
</form>
