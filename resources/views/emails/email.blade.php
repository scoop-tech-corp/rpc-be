<!DOCTYPE html>
<html>

<head>
  <title>RPC PetShop</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header text-center font-weight-bold">
      </div>
      <div class="card-body">
        <form name="add-blog-post-form" id="add-blog-post-form" method="post" action="{{url('store-form')}}">
          @csrf
          <div class="form-group">
            <label for="exampleInputEmail1">Name : </label> {{$data['name']}}
          </div>
          <br>
          <div class="form-group">
            <label for="exampleInputEmail1">Email : </label> {{$data['email']}}
          </div>
          <br>
          <div class="form-group">
            <label for="exampleInputEmail1">Role : </label> {{$data['jobTitle']}}
          </div>
          <br>
          <div class="form-group">
            <label for="exampleInputEmail1">For verification please use link down below</label>
          </div>
          <br>
          <div class="form-group">
          
          <a href="{{URL::to('/')}}/posts/{{$data['usersId']}}">Link Verification Email</a>
            <!-- <a href="http://127.0.0.1:8000/posts/{{$data['usersId']}}">Link Verification Email</a> -->
            <!-- <a href="https://dev-radhiyanpetncare.vercel.app/posts/{{$data['usersId']}}">Link Verification Email</a> -->
          
          </div>
        </form>
      </div>
    </div>
  </div>
</body>

</html>