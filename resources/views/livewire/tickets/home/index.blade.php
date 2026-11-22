<div>
    <div class="content-wrapper">
        <div class="row">
          <div class="col-md-12 grid-margin">
            <div class="row">
              <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                <h3 class="font-weight-bold">Welcome {{ Auth::user()->name }}</h3>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 grid-margin stretch-card">
            
              
              
            <div class="card tale-bg-dynamic">
              <div class="tale-content">
                <div class="weather-info">
                  
                  <div class="d-flex">
                    <div>
                      <h3 id="greeting" class="mb-3"></h3>
                      
                    </div>
                    
                    {{-- <div class="ms-2">
                      <h4 class="location font-weight-normal">Chicago</h4>
                      <h6 class="font-weight-normal">Illinois</h6>
                    </div> --}}
                  </div>
                  
                </div>
                
              </div>
            </div>
            
          </div>
          <div class="col-md-6 grid-margin transparent">
            <div class="row">
              <div class="col-md-6 mb-4 stretch-card transparent">
                <div class="card card-tale">
                  <div class="card-body">
                    <p class="mb-4">My Tickets</p>
                    <p class="fs-30 mb-2">4006</p>
                    <p>10.00% (30 days)</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4 stretch-card transparent">
                <div class="card card-dark-blue">
                  <div class="card-body">
                    <p class="mb-4">Total Solved</p>
                    <p class="fs-30 mb-2">61344</p>
                    <p>22.00% (30 days)</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
                <div class="card card-light-blue">
                  <div class="card-body">
                    <p class="mb-4">Total Pending</p>
                    <p class="fs-30 mb-2">34040</p>
                    <p>2.00% (30 days)</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6 stretch-card transparent">
                <div class="card card-light-danger">
                  <div class="card-body">
                    <p class="mb-4">Totla Progres</p>
                    <p class="fs-30 mb-2">47033</p>
                    <p>0.22% (30 days)</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <p class="card-title">Tickets Report</p>
                  <a href="#" class="text-info">View all</a>
                </div>
                <p class="font-weight-500">The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc</p>
                <div id="sales-chart-legend" class="chartjs-legend mt-4 mb-2"></div>
                <canvas id="sales-chart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
            <div class="card position-relative">
              <div class="card-body">
                <div id="detailedReports" class="carousel slide detailed-report-carousel position-static pt-2" data-bs-ride="carousel">
                  <div class="carousel-inner">
                    <div class="carousel-item active">
                      <div class="row">
                        <div class="col-md-12 col-xl-3 d-flex flex-column justify-content-start">
                          <div class="ml-xl-4 mt-3">
                            <p class="card-title">Detailed Reports</p>
                            <h1 class="text-primary">$34040</h1>
                            <h3 class="font-weight-500 mb-xl-4 text-primary">North America</h3>
                            <p class="mb-2 mb-xl-0">The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc</p>
                          </div>
                        </div>
                        <div class="col-md-12 col-xl-9">
                          <div class="row">
                            <div class="col-md-6 border-right">
                              <div class="table-responsive mb-3 mb-md-0 mt-3">
                                <table class="table table-borderless report-table">
                                  <tr>
                                    <td class="text-muted">Illinois</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">713</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Washington</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">583</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Mississippi</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 95%" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">924</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">California</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">664</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Maryland</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">560</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Alaska</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">793</h5>
                                    </td>
                                  </tr>
                                </table>
                              </div>
                            </div>
                            <div class="col-md-6 mt-3">
                              <div class="daoughnutchart-wrapper">
                                <canvas id="north-america-chart"></canvas>
                              </div>
                              <div id="north-america-chart-legend">
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="carousel-item">
                      <div class="row">
                        <div class="col-md-12 col-xl-3 d-flex flex-column justify-content-start">
                          <div class="ml-xl-4 mt-3">
                            <p class="card-title">Detailed Reports</p>
                            <h1 class="text-primary">$34040</h1>
                            <h3 class="font-weight-500 mb-xl-4 text-primary">North America</h3>
                            <p class="mb-2 mb-xl-0">The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc</p>
                          </div>
                        </div>
                        <div class="col-md-12 col-xl-9">
                          <div class="row">
                            <div class="col-md-6 border-right">
                              <div class="table-responsive mb-3 mb-md-0 mt-3">
                                <table class="table table-borderless report-table">
                                  <tr>
                                    <td class="text-muted">Illinois</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">713</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Washington</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">583</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Mississippi</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 95%" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">924</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">California</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">664</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Maryland</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">560</h5>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td class="text-muted">Alaska</td>
                                    <td class="w-100 px-0">
                                      <div class="progress progress-md mx-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </td>
                                    <td>
                                      <h5 class="font-weight-bold mb-0">793</h5>
                                    </td>
                                  </tr>
                                </table>
                              </div>
                            </div>
                            <div class="col-md-6 mt-3">
                              <div class="daoughnutchart-wrapper">
                                <canvas id="south-america-chart"></canvas>
                              </div>
                              <div id="south-america-chart-legend"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <a class="carousel-control-prev" href="#detailedReports" role="button" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                  </a>
                  <a class="carousel-control-next" href="#detailedReports" role="button" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <p class="card-title mb-0">Assigned Tickets</p>
                <div class="table-responsive">
                  <table class="table table-striped table-borderless">
                    <thead>
                      <tr>
                        <th>Requester</th>
                        <th>Department</th>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Sub Title</th>
                        <th>Reported On</th>
                        <th>Status</th>
                        <th>Details</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>John Smith</td>
                        <td class="font-weight-bold">Marketing</td>
                        <td>SEO</td>
                        <td class="font-weight-medium">Website Ranking Optimization</td>
                        <td>Increase Google ranking</td>
                        <td>21 Sep 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-success">Completed</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                      <tr>
                        <td>Jane Doe</td>
                        <td class="font-weight-bold">Sales</td>
                        <td>Advertising</td>
                        <td class="font-weight-medium">Display Ad Campaign</td>
                        <td>Banner Ad for Q4</td>
                        <td>28 Sep 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-warning">Pending</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                      <tr>
                        <td>Michael Tan</td>
                        <td class="font-weight-bold">Finance</td>
                        <td>PPC</td>
                        <td class="font-weight-medium">Pay Per Click Review</td>
                        <td>Review monthly spend</td>
                        <td>30 Jun 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-warning">Pending</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                      <tr>
                        <td>Sarah Lee</td>
                        <td class="font-weight-bold">IT</td>
                        <td>Email Marketing</td>
                        <td class="font-weight-medium">Quarterly Newsletter</td>
                        <td>Content review</td>
                        <td>01 Nov 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-danger">Cancelled</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                      <tr>
                        <td>Adam Wilson</td>
                        <td class="font-weight-bold">HR</td>
                        <td>Referral Program</td>
                        <td class="font-weight-medium">Employee Referral</td>
                        <td>Campaign launch</td>
                        <td>20 Mar 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-warning">Pending</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                      <tr>
                        <td>Amanda Jones</td>
                        <td class="font-weight-bold">PR</td>
                        <td>Social Media</td>
                        <td class="font-weight-medium">Social Media Strategy</td>
                        <td>Plan for new quarter</td>
                        <td>26 Oct 2018</td>
                        <td class="font-weight-medium">
                          <span class="badge badge-success">Completed</span>
                        </td>
                        <td><a href="#" class="text-info">View</a></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
</div>
@push('scripts')
<script>
    const card = document.querySelector('.tale-bg-dynamic');
    const morningImg = @json(asset('tickets/assets/images/bg/Morning.jpg'));
    const dayImg     = @json(asset('tickets/assets/images/bg/Afternoon.jpg'));
    const eveningImg = @json(asset('tickets/assets/images/bg/Evening.jpg'));
    const nightImg   = @json(asset('tickets/assets/images/bg/Night.jpg'));
    const greetingBox = document.getElementById('greeting');
  
    function updateTheme() {
      const hour = new Date().getHours();
      let bgImage = "";
      let greeting = "";
  
      if (hour >= 5 && hour < 11) {
        bgImage = morningImg;
        greeting = "Good Morning 🌅";
        card.style.setProperty("--overlay", "rgba(0,0,0,0.25)");
      } 
      else if (hour >= 11 && hour < 16) {
        bgImage = dayImg;
        greeting = "Good Afternoon ☀️";
        card.style.setProperty("--overlay", "rgba(0,0,0,0.35)");
      } 
      else if (hour >= 16 && hour < 18) {
        bgImage = eveningImg;
        greeting = "Good Evening 🌇";
        card.style.setProperty("--overlay", "rgba(0,0,0,0.4)");
      } 
      else {
        bgImage = nightImg;
        greeting = "Good Night 🌙";
        card.style.setProperty("--overlay", "rgba(0,0,0,0.55)");
      }
  
      card.style.backgroundImage = `url('${bgImage}')`;
      if (greetingBox) greetingBox.innerText = greeting;
    }
  
    updateTheme();
  </script>
@endpush
