<div class="content-wrapper">   
    <section class="content-header">
        <h1>
            <i class="fa fa-user-plus"></i> <?php echo $this->lang->line('student_information'); ?> <small><?php echo $this->lang->line('student'); ?></small></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">             
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo $title; ?></h3>
                    </div>
                    <div class="box-body no-padding">
                        <?php
                        $count = 1;

                        foreach ($studentlist as $student) {
                            ?>
                            <div class="row carousel-row">
                                <div class="col-xs-8 col-xs-offset-2 slide-row">
                                    <div id="carousel-2" class="carousel slide slide-carousel" data-ride="carousel">                                        
                                        <div class="carousel-inner">
                                            <div class="item active">
                                                <img src="<?php echo $this->media_storage->getImageURL($student['image']) ?>" alt="Image">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="slide-content">
                                        <h4><a href="<?php echo base_url(); ?>student/view/<?php echo $student['id'] ?>"> <?php echo $this->customlib->getFullName($student['firstname'],$student['middlename'],$student['lastname'],$sch_setting->middlename,$sch_setting->lastname); ?></a></h4>
                                        <address>
                                            <strong><?php echo $student['class'] . "(" . $student['section'] . ")" ?></strong><br>
                                            <b><?php echo $this->lang->line('admission_no'); ?>: </b><?php echo $student['admission_no'] ?><br/>
                                            <b><?php echo $this->lang->line('roll_no'); ?> : </b><?php echo $student['roll_no'] ?><br>
                                            <b><?php echo $this->lang->line('date_of_birth'); ?> : </b> <?php echo date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($student['dob'])); ?>
                                            <br>
                                            <abbr title="Phone"><i class="fa fa-phone-square"></i>&nbsp;&nbsp;</abbr> <?php echo $student['mobileno'] ?>
                                        </address>
                                        <address>
                                            <a href="mailto:#"><i class="fa fa-at"></i>&nbsp;&nbsp;<?php echo $student['email'] ?></a>
                                        </address>
                                    </div>
                                    <div class="slide-footer">
                                        <span class="pull-right buttons">
                                          
                                            
                                            <!--// 12-6-2025 by bhavesh-->
                                            <a href="<?php echo base_url(); ?>('lc/generate_lc/' . $student['id']); ?>" class="btn btn-primary btn-sm" title="Generate LC">
                                                Generate LC
                                                <i class="fa fa-pencil"></i>
                                            </a>


                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $count++;
                        }
                        ?>
                    </div>
                    <div class="box-footer">
                        <div class="mailbox-controls">   
                            <div class="pull-right">
                                1-50/200
                                <div class="btn-group">
                                    <button class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></button>
                                    <button class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>       
    </section>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#btnreset").click(function () {
            $("#form1")[0].reset();
        });
    });
</script>